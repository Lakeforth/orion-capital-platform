<?php

namespace App\Services\GoogleDrive;

use App\Models\GoogleDriveConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The OAuth half of the Drive integration: consent URL, code exchange, token refresh, revoke.
 *
 * Credentials are resolved PER COMPANY, not straight from config. A tenant may bring its own
 * Google Cloud project (stored on its connection row) — otherwise it rides on the platform's
 * client from config/services.php. See GoogleDriveConnection::oauthClient().
 *
 * The redirect URI always stays platform-level: it is this CRM's own callback URL, so a tenant
 * using its own client must register OUR URI in THEIR console. There is only ever one.
 *
 * Written against Laravel's HTTP client rather than google/apiclient — it is four endpoints,
 * and the official SDK brings a large dependency tree for very little here.
 *
 * Nothing in this class ever logs a token or a client secret.
 */
class GoogleOAuth
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    /** The platform-wide client, used by every tenant that has not configured its own. */
    public function platformConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled($this->redirectUri());
    }

    /** Can this specific company start a connection — via its own client or the platform's? */
    public function configuredFor(?GoogleDriveConnection $connection): bool
    {
        if (blank($this->redirectUri())) {
            return false;
        }

        $client = $connection?->oauthClient() ?? [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
        ];

        return filled($client['client_id']) && filled($client['client_secret']);
    }

    /**
     * The OAuth callback URL.
     *
     * Derived from the host currently being served, NOT from a single configured value:
     * one install backs many broker backoffices on different hostnames
     * (backoffice-crm.<tenant>.com, secure.<tenant>.com, ...), so a static URI would send
     * every tenant's consent round-trip to whichever host happened to be configured.
     *
     * Deriving it is safe for Google's exact-match rule because both legs — the authorize
     * redirect and the token exchange — happen inside the same request host, so both present
     * an identical string. Each tenant registers its own host in its own Google project.
     *
     * GOOGLE_REDIRECT_URI still wins when set, for single-tenant installs that want it fixed.
     */
    public function redirectUri(): ?string
    {
        if (filled(config('services.google.redirect'))) {
            return config('services.google.redirect');
        }

        // No request context (queued job, console): nothing to derive a host from. Refreshing
        // a token does not need the redirect URI, so this is only ever null off the web path.
        if (! app()->bound('request') || ! request()?->getHost()) {
            return null;
        }

        return route('admin.leadmax.settings.google-drive.callback');
    }

    /** @return array<int,string> */
    public function scopes(): array
    {
        return (array) config('services.google.scopes', []);
    }

    /**
     * Where to send the admin to grant consent.
     *
     * access_type=offline and prompt=consent together are what guarantee a refresh token.
     * Without prompt=consent Google omits it on every grant after the first, producing a
     * connection that works until the access token expires and then dies with no way back —
     * the classic failure in this integration.
     */
    public function authorizationUrl(?GoogleDriveConnection $connection, string $state): string
    {
        $client = $this->clientFor($connection);

        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => $client['client_id'],
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes()),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /**
     * Swap the one-time code for tokens.
     *
     * @return array{access_token:string,refresh_token:?string,expires_in:int,scope:string}
     */
    public function exchangeCode(?GoogleDriveConnection $connection, string $code): array
    {
        $client = $this->clientFor($connection);

        $res = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $client['client_id'],
            'client_secret' => $client['client_secret'],
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if ($res->failed()) {
            Log::warning('Google OAuth code exchange failed', [
                'status' => $res->status(),
                'error' => $res->json('error'),
            ]);
            throw new RuntimeException('Google rejected the authorisation code.');
        }

        return $res->json();
    }

    /** The email/name of the account that just consented, for display in Settings. */
    public function userInfo(string $accessToken): array
    {
        $res = Http::withToken($accessToken)->timeout(15)->get(self::USERINFO_URL);

        return $res->successful() ? $res->json() : [];
    }

    /**
     * Return a valid access token for this connection, refreshing if needed.
     *
     * Takes a short lock around the refresh so several queued jobs waking at once do not each
     * burn a refresh and race to write a different token back.
     */
    public function accessTokenFor(GoogleDriveConnection $connection): string
    {
        if (! $connection->needsRefresh()) {
            return $connection->access_token;
        }

        $lock = cache()->lock('google-drive-refresh:'.$connection->id, 15);

        try {
            $lock->block(10);
        } catch (\Throwable) {
            // Someone else is refreshing. Re-read: they have almost certainly written a token.
            $connection->refresh();
            if (! $connection->needsRefresh()) {
                return $connection->access_token;
            }
        }

        try {
            $connection->refresh();
            if (! $connection->needsRefresh()) {
                return $connection->access_token;
            }

            return $this->refreshAccessToken($connection);
        } finally {
            optional($lock)->release();
        }
    }

    private function refreshAccessToken(GoogleDriveConnection $connection): string
    {
        if (blank($connection->refresh_token)) {
            $connection->markFailed('No refresh token held; reconnect required.');
            throw new GoogleDriveNotConnected('This company has no usable Google Drive refresh token.');
        }

        $client = $this->clientFor($connection);

        $res = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'client_id' => $client['client_id'],
            'client_secret' => $client['client_secret'],
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($res->failed()) {
            $error = (string) $res->json('error');

            // invalid_grant means the user revoked access, changed password, or the token aged
            // out. Terminal: no retry fixes it, a human must reconnect.
            if ($error === 'invalid_grant') {
                $connection->markFailed('Access was revoked in Google. Reconnect required.');
                throw new GoogleDriveNotConnected('Google access was revoked for this company.');
            }

            Log::warning('Google token refresh failed', [
                'company_id' => $connection->company_id,
                'status' => $res->status(),
                'error' => $error,
            ]);
            throw new RuntimeException('Could not refresh the Google access token.');
        }

        $connection->forceFill([
            'access_token' => $res->json('access_token'),
            'access_token_expires_at' => now()->addSeconds((int) $res->json('expires_in', 3600)),
            'last_error' => null,
            'last_error_at' => null,
        ])->save();

        return $connection->access_token;
    }

    /**
     * Tell Google to forget us. Best effort: if the call fails we still drop our copy, because
     * keeping a token we can no longer manage is worse than a stale grant on Google's side.
     */
    public function revoke(GoogleDriveConnection $connection): void
    {
        $token = $connection->refresh_token ?: $connection->access_token;

        if (blank($token)) {
            return;
        }

        try {
            Http::asForm()->timeout(10)->post(self::REVOKE_URL, ['token' => $token]);
        } catch (\Throwable $e) {
            Log::info('Google token revoke call failed; dropping local credentials anyway', [
                'company_id' => $connection->company_id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** @return array{client_id:?string,client_secret:?string,picker_api_key:?string,project_number:?string,own:bool} */
    private function clientFor(?GoogleDriveConnection $connection): array
    {
        $client = $connection?->oauthClient() ?? [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'picker_api_key' => config('services.google.picker_api_key'),
            'project_number' => config('services.google.project_number'),
            'own' => false,
        ];

        if (blank($client['client_id']) || blank($client['client_secret'])) {
            throw new GoogleDriveNotConnected('No Google client credentials are configured for this company.');
        }

        return $client;
    }
}
