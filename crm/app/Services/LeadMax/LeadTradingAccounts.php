<?php

namespace App\Services\LeadMax;

use App\Models\Company;
use App\Models\Role;
use App\Models\TradingAccount;
use App\Models\User;
use App\Services\TradingAccountSetupService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * "Trading Account for Every Lead" module (Settings > Modules). While it is on, each new lead gets a live
 * trading account and existing leads are backfilled, so Login as Client opens the trade room for every lead.
 * Turning it off stops new accounts; the super admin chooses to keep the accounts it created or remove the unused ones.
 */
class LeadTradingAccounts
{
    /** Module key on Settings > Modules (super admin), read like every other module. */
    public const MODULE = 'lead_trading_accounts';

    public function __construct(private TradingAccountSetupService $setup) {}

    public function enabled(?Company $company = null): bool
    {
        if (! $company) {
            return module_enabled(self::MODULE);
        }
        $settings = is_string($company->settings) ? json_decode($company->settings, true) : $company->settings;

        return in_array($settings[self::MODULE] ?? 'no', [true, 1, '1', 'true', 'yes', 'on'], true);
    }

    /** The lead's trading account (live first), creating a live one when it has none. */
    public function ensureFor(User $lead): TradingAccount
    {
        $account = $lead->tradingAccounts()->orderByRaw('account_type = ? desc', [TradingAccount::TYPE_LIVE])->orderBy('created_at')->first();
        if ($account) {
            return $account;
        }
        $account = $this->setup->setupDefaultLiveAccount($lead);
        // Tag it, so turning the module off can offer to remove only what the module created.
        if ($account->wasRecentlyCreated) {
            $account->forceFill(['config' => [...($account->config ?? []), 'created_by' => self::MODULE]])->saveQuietly();
        }

        return $account;
    }

    /** Accounts this module created, split into ones that are safe to remove (never used) and ones with activity. */
    public function createdAccounts(Company $company): Builder
    {
        return TradingAccount::withoutGlobalScope('company')->where('company_id', $company->id)->where('config->created_by', self::MODULE);
    }

    public function removableAccounts(Company $company): Builder
    {
        return $this->createdAccounts($company)
            ->where(fn ($q) => $q->whereNull('balance')->orWhere('balance', 0))
            ->where(fn ($q) => $q->whereNull('deposit')->orWhere('deposit', 0))
            ->where(fn ($q) => $q->whereNull('credit')->orWhere('credit', 0))
            ->where(fn ($q) => $q->whereNull('bonus')->orWhere('bonus', 0))
            ->where(fn ($q) => $q->whereNull('profit')->orWhere('profit', 0))
            ->whereDoesntHave('trades', fn ($q) => $q->withTrashed())
            ->whereNotExists(fn ($q) => $q->from('trading_account_component_movements')->whereColumn('trading_account_component_movements.trading_account_id', 'trading_accounts.id'))
            ->whereNotExists(fn ($q) => $q->from('challenges')->whereColumn('challenges.trading_account_id', 'trading_accounts.id'))
            ->whereNotExists(fn ($q) => $q->from('trading_account_admin_actions')->where(fn ($w) => $w->whereColumn('trading_account_admin_actions.account_id', 'trading_accounts.id')->orWhereColumn('trading_account_admin_actions.destination_id', 'trading_accounts.id')));
    }

    /** Laratrust role.added listener: a user who just became a lead gets an account when the setting is on. */
    public function onRoleAdded(User $user, mixed $role): void
    {
        $name = $role instanceof Role ? $role->name : Role::query()->whereKey($role)->value('name');
        if ($name !== 'lead') {
            return;
        }
        $company = app()->bound('company') && app('company')?->id === $user->company_id
            ? app('company')
            : Company::withoutGlobalScopes()->find($user->company_id);
        if (! $company || ! $this->enabled($company)) {
            return;
        }
        // A failed account must never block the lead itself (manual add, import, affiliate API).
        try {
            $this->ensureFor($user);
        } catch (\Throwable $e) {
            Log::error('leadmax.trading_account.auto_create_failed', ['user_id' => $user->id, 'message' => $e->getMessage()]);
        }
    }
}
