<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;

class RestrictedManagerAccess
{
    public static function applies(?User $user): bool
    {
        return $user && $user->hasRole('manager')
            && ! $user->hasRole(['admin', 'superadmin', 'tenant_admin'])
            && (($user->company?->settings ?? [])['restricted_manager_enabled'] ?? 'no') === 'yes';
    }

    public static function viewingAsCustomer(Request $request): bool
    {
        $token = $request->user()?->currentAccessToken();
        if ($token && in_array('restricted-manager-view', $token->abilities ?? [], true)) {
            return true;
        }

        foreach ($token->abilities ?? [] as $ability) {
            if (str_starts_with($ability, 'manager-actor:')) {
                $actor = User::withoutGlobalScope('company')->find(substr($ability, strlen('manager-actor:')));

                return ! $actor || ((string) $actor->company_id === (string) $request->user()->company_id && self::applies($actor));
            }
        }

        if ($request->hasSession()) {
            if ($request->session()->get('restricted_manager_impersonation', false)) {
                return true;
            }
            if ($actorId = $request->session()->get('admin_login_id')) {
                return self::applies(User::withoutGlobalScope('company')->find($actorId));
            }
        }

        return false;
    }

    public static function requiresNewCustomerSession(Request $request): bool
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();
        if (! $user || ! ($token->id ?? null) || ! $user->hasRole(['customer', 'user'])
            || $user->hasRole(['admin', 'superadmin', 'tenant_admin', 'manager', 'agent'])) {
            return false;
        }
        $abilities = $token->abilities ?? [];
        if (in_array('restricted-manager-view', $abilities, true)
            || collect($abilities)->contains(fn ($ability) => str_starts_with($ability, 'manager-actor:'))) {
            return false;
        }

        $cutoff = (int) (($user->company?->settings ?? [])['restricted_manager_legacy_token_max_id'] ?? 0);

        return $cutoff > 0 && (int) $token->id <= $cutoff;
    }

    public static function active(): bool
    {
        return self::applies(auth()->user()) || self::viewingAsCustomer(request());
    }

    public static function allowedAdminRoute(?string $route): bool
    {
        return in_array($route, [
            'admin.dashboard', 'admin.central', 'admin.users.index', 'admin.users.customers',
            'admin.users.show', 'admin.users.managed_profile', 'admin.users.next',
            'admin.users.login_as', 'admin.frontend.login', 'admin.login_as_admin',
            'admin.user.trading_accounts', 'admin.user.trading_accounts.show',
            'admin.user.positions', 'admin.user.closed_positions', 'admin.user.trades',
            'admin.user.transactions', 'admin.user.activity', 'admin.user.notes', 'admin.user.comments',
            'admin.user.withdrawal', 'admin.user.deposit', 'admin.user.wallets',
            'admin.trades', 'user.open_trades', 'user.closed_trades',
            'admin.notifications.index', 'admin.profile',
            'tenant.logout', 'logout',
        ], true);
    }

    public static function customer(User $user): void
    {
        abort_unless($user->company_id === auth()->user()->company_id
            && $user->hasRole(['customer', 'user'])
            && ! $user->hasRole(['admin', 'superadmin', 'tenant_admin', 'manager', 'agent']), 403);
    }
}
