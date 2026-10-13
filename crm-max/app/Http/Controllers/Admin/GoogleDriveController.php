<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleDriveConnection;
use App\Services\GoogleDrive\GoogleDriveService;
use App\Services\GoogleDrive\GoogleOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Linking a company to a Google Drive account.
 *
 * One connection per company: whichever admin completes the flow is the account the whole
 * tenant then uses. Restricted to admin / tenant_admin / superadmin via is_admin(), because
 * connecting hands the CRM ongoing access to a real person's Google account.
 */
class GoogleDriveController extends Controller
{
    /** Session key holding the one-time CSRF state for the consent round-trip. */
    private const STATE_KEY = 'google_drive_oauth_state';

    public function __construct(
        private readonly GoogleOAuth $oauth,
        private readonly GoogleDriveService $drive,
    ) {}

    /** Send the admin to Google's consent screen. */
    public function connect(Request $request)
    {
        $this->authorizeAdmin();

        $connection = $this->drive->connectionFor(app('company')->id);

        if (! $this->oauth->configuredFor($connection)) {
            return back()->with('warning',
                'Google Drive has no client credentials yet. Add them below, or ask your system administrator to configure them for the whole platform.');
        }

        // The state carries the company so the callback cannot be replayed against a different
        // tenant, plus a nonce we compare against the session to stop CSRF.
        $nonce = Str::random(40);
        $request->session()->put(self::STATE_KEY, [
            'nonce' => $nonce,
            'company_id' => app('company')->id,
        ]);

        return redirect()->away($this->oauth->authorizationUrl($connection, $nonce));
    }

    /** Google sends the admin back here with a one-time code. */
    public function callback(Request $request)
    {
        $this->authorizeAdmin();

        $expected = $request->session()->pull(self::STATE_KEY);
        $companyId = app('company')->id;

        if (! is_array($expected)
            || ! hash_equals((string) ($expected['nonce'] ?? ''), (string) $request->query('state'))
            || (int) ($expected['company_id'] ?? 0) !== (int) $companyId) {
            return $this->backToSettings()->with('warning', 'That Google sign-in could not be verified. Please try again.');
        }

        if ($request->query('error')) {
            // The admin pressed Cancel. Not an error worth shouting about.
            return $this->backToSettings()->with('warning', 'Google Drive was not connected.');
        }

        if (! $request->filled('code')) {
            return $this->backToSettings()->with('warning', 'Google did not return an authorisation code.');
        }

        $connection = $this->drive->connectionFor($companyId);

        try {
            $tokens = $this->oauth->exchangeCode($connection, $request->query('code'));
        } catch (\Throwable $e) {
            Log::warning('Google Drive connect failed', ['company_id' => $companyId, 'message' => $e->getMessage()]);

            return $this->backToSettings()->with('warning', 'Could not complete the Google connection. Please try again.');
        }

        // Without a refresh token the connection dies as soon as the access token expires, so
        // refuse it outright rather than storing something that will fail silently in an hour.
        if (blank($tokens['refresh_token'] ?? null)) {
            return $this->backToSettings()->with('warning',
                'Google did not return a long-lived token. Remove '.get_site_name().' CRM from your Google account permissions and connect again.');
        }

        $profile = $this->oauth->userInfo($tokens['access_token'] ?? '');

        GoogleDriveConnection::query()->updateOrCreate(
            ['company_id' => $companyId],
            [
                'google_email' => $profile['email'] ?? null,
                'google_name' => $profile['name'] ?? null,
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'],
                'access_token_expires_at' => now()->addSeconds((int) ($tokens['expires_in'] ?? 3600)),
                'scopes' => explode(' ', (string) ($tokens['scope'] ?? '')),
                'connected_by_user_id' => $request->user()?->id,
                'connected_at' => now(),
                'last_error' => null,
                'last_error_at' => null,
            ],
        );

        Log::info('Google Drive connected', [
            'company_id' => $companyId,
            'user_id' => $request->user()?->id,
            'google_email' => $profile['email'] ?? null,
        ]);

        return $this->backToSettings()->with('success',
            'Google Drive connected'.(isset($profile['email']) ? ' as '.$profile['email'] : '').'.');
    }

    /** Unlink, and tell Google to forget us too. */
    public function disconnect(Request $request)
    {
        $this->authorizeAdmin();

        $connection = $this->drive->connectionFor(app('company')->id);

        if ($connection) {
            $this->oauth->revoke($connection);

            // Clear the credentials and the identity, but keep the row: it also holds this
            // tenant's own OAuth client and chosen export folder, which should survive a
            // disconnect/reconnect rather than having to be set up again.
            $connection->forceFill([
                'access_token' => null,
                'refresh_token' => null,
                'access_token_expires_at' => null,
                'google_email' => null,
                'google_name' => null,
                'scopes' => null,
                'connected_at' => null,
                'connected_by_user_id' => null,
                'last_error' => null,
                'last_error_at' => null,
            ])->save();

            Log::info('Google Drive disconnected', [
                'company_id' => app('company')->id,
                'user_id' => $request->user()?->id,
            ]);
        }

        return $this->backToSettings()->with('success', 'Google Drive disconnected.');
    }

    /** Remember where exports should land. Set by the admin from the Drive folder picker. */
    public function saveExportFolder(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'folder_id' => ['required', 'string', 'max:255'],
            'folder_name' => ['required', 'string', 'max:255'],
        ]);

        $connection = $this->drive->connectionFor(app('company')->id);

        if (! $connection) {
            return $this->backToSettings()->with('warning', 'Connect Google Drive first.');
        }

        $connection->forceFill([
            'export_folder_id' => $data['folder_id'],
            'export_folder_name' => $data['folder_name'],
        ])->save();

        return $this->backToSettings()->with('success', 'Exports will be saved to “'.$data['folder_name'].'”.');
    }

    /**
     * Short-lived credentials for the Google Picker, which runs in the browser and therefore
     * needs an access token of its own.
     *
     * Safe only because the token carries the narrow drive.file scope: it lets the picker show
     * the user their own Drive and hand us the one file they choose. It grants the page no
     * standing read of their Drive. Admin-only, and never cached.
     */
    public function pickerConfig(Request $request)
    {
        $this->authorizeAdmin();

        $companyId = app('company')->id;
        $connection = $this->drive->connectionFor($companyId);

        if (! $connection || ! $connection->isUsable()) {
            return response()->json(['connected' => false], 409);
        }

        $client = $connection->oauthClient();

        return response()->json([
            'connected' => true,
            'accessToken' => $this->oauth->accessTokenFor($connection),
            'apiKey' => $client['picker_api_key'],
            'appId' => $client['project_number'],
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * Store this company's own Google Cloud credentials.
     *
     * Optional. Most tenants leave this empty and ride on the platform's OAuth client — they
     * just press Connect. A tenant fills this in when it wants its own consent-screen branding,
     * its own API quota, or to carry its own Google verification rather than ours.
     *
     * Changing the client invalidates any existing grant, so the tokens are cleared and the
     * admin has to reconnect — otherwise we would be refreshing tokens against a client that
     * did not issue them.
     */
    public function saveCredentials(Request $request)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'picker_api_key' => ['nullable', 'string', 'max:255'],
            'project_number' => ['nullable', 'string', 'max:64'],
        ]);

        // Both halves or neither: a client id without a secret cannot complete the exchange.
        if (filled($data['client_id']) !== filled($data['client_secret'])) {
            return $this->backToSettings()->with('warning', 'Enter both the Client ID and the Client secret, or leave both blank to use the platform credentials.');
        }

        $companyId = app('company')->id;
        $connection = GoogleDriveConnection::query()->firstOrNew(['company_id' => $companyId]);

        $clientChanged = (string) $connection->client_id !== (string) $data['client_id'];

        $connection->fill([
            'company_id' => $companyId,
            'client_id' => $data['client_id'] ?: null,
            // Blank secret with an unchanged id means "leave the stored secret alone" — the
            // form never echoes the secret back, so an empty box must not wipe it.
            'client_secret' => filled($data['client_secret'])
                ? $data['client_secret']
                : ($clientChanged ? null : $connection->client_secret),
            'picker_api_key' => $data['picker_api_key'] ?: null,
            'project_number' => $data['project_number'] ?: null,
        ]);

        if ($clientChanged) {
            $connection->fill([
                'access_token' => null,
                'refresh_token' => null,
                'access_token_expires_at' => null,
                'google_email' => null,
                'google_name' => null,
                'connected_at' => null,
                'last_error' => null,
                'last_error_at' => null,
            ]);
        }

        $connection->save();

        Log::info('Google Drive OAuth client updated', [
            'company_id' => $companyId,
            'user_id' => $request->user()?->id,
            'uses_own_client' => $connection->usesOwnOAuthClient(),
        ]);

        return $this->backToSettings()->with('success', $clientChanged
            ? 'Google credentials saved. Connect the account to finish.'
            : 'Google credentials saved.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(is_admin(), 403, 'Only an administrator can manage the Google Drive connection.');
    }

    private function backToSettings()
    {
        return redirect()->route('admin.leadmax.settings', ['tab' => 'integrations']);
    }
}
