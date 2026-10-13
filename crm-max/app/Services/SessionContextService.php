<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Stamps device/browser/IP context onto Sanctum tokens (so the Security
 * page's "Active Sessions" list is real) and records login history rows.
 */
class SessionContextService
{
    /** Called right after a successful login + token issue. */
    public function recordLogin(User $user, Request $request, ?PersonalAccessToken $token): void
    {
        $ctx = $this->context($request);

        if ($token) {
            $token->forceFill([
                'ip_address' => $ctx['ip'],
                'device_type' => $ctx['device_type'],
                'browser' => $ctx['browser'],
                'platform' => $ctx['platform'],
                'last_active_at' => now(),
            ])->save();
        }

        $this->writeHistory($user->id, $ctx, 'success');
    }

    /** Called when a login attempt fails but the email maps to a real user. */
    public function recordFailed(?User $user, Request $request): void
    {
        if (! $user) {
            return;
        }
        $this->writeHistory($user->id, $this->context($request), 'failed');
    }

    /** Middleware hook: keep the current token's last_active_at fresh. */
    public function touch(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            return;
        }

        $ctx = $this->context($request);
        $needsBackfill = ! $token->browser && $ctx['browser'];
        $isStale = ! $token->last_active_at || $token->last_active_at->lt(now()->subMinutes(5));

        // Avoid a DB write on every single authenticated request -- only when
        // the activity timestamp is stale or metadata is still missing.
        if (! $needsBackfill && ! $isStale) {
            return;
        }

        $updates = ['last_active_at' => now()];
        if ($needsBackfill) {
            $updates['browser'] = $ctx['browser'];
            $updates['platform'] = $ctx['platform'];
            $updates['device_type'] = $ctx['device_type'];
            $updates['ip_address'] = $ctx['ip'];
        }

        $token->forceFill($updates)->save();
    }

    private function context(Request $request): array
    {
        $parsed = UserAgentParser::parse($request->userAgent());

        return [
            'ip' => $request->ip(),
            'browser' => $parsed['browser'],
            'platform' => $parsed['platform'],
            'device_type' => $parsed['device_type'],
        ];
    }

    private function writeHistory(string $userId, array $ctx, string $status): void
    {
        LoginHistory::create([
            'user_id' => $userId,
            'ip_address' => $ctx['ip'],
            'device_type' => $ctx['device_type'],
            'browser' => $ctx['browser'],
            'platform' => $ctx['platform'],
            // Country of the IP (DB-IP Lite); null, shown as Unknown, when it has none.
            'location' => app(IpCountry::class)->lookup($ctx['ip'])['name'] ?? null,
            'status' => $status,
        ]);
    }
}
