<?php

namespace App\Support\LeadMax;

use App\Models\User;
use App\Services\IpCountry;
use App\Services\UserAgentParser;

/**
 * Who did something and from what, captured when an activity row is written.
 *
 * The role is a snapshot: a later promotion must not rewrite what history says the
 * person was at the time. Roles outside the four the CRM reports on keep their own
 * name instead of being passed off as Admin.
 */
class ActivityContext
{
    public static function roleLabel(?User $user): string
    {
        if (! $user) {
            return 'System';
        }

        $roles = $user->getRoles();

        return match (true) {
            (bool) array_intersect($roles, ['superadmin', 'admin', 'tenant_admin']) => 'Admin',
            in_array('manager', $roles, true) => 'Manager',
            in_array('agent', $roles, true) => 'Agent',
            (bool) array_intersect($roles, ['lead', 'customer', 'user']) => 'Client',
            default => $roles ? ucfirst((string) $roles[0]) : 'Unknown',
        };
    }

    /** Actor role, browser, device and IP country of the current request, for ActivityLog info. */
    public static function current(): array
    {
        $agent = UserAgentParser::parse(request()->userAgent());

        return array_filter([
            'actor_role' => self::roleLabel(auth()->user()),
            'browser' => $agent['browser'] ?: null,
            'platform' => $agent['platform'] ?: null,
            'device' => $agent['device_type'] ?: null,
            // Set while an admin is signed in as another staff member.
            'impersonator_id' => session('admin_login_id'),
            // Country, code, source and lookup time; absent (shown as Unknown) when the IP has none.
            ...app(IpCountry::class)->context(request()->ip()),
        ], fn ($value) => $value !== null);
    }
}
