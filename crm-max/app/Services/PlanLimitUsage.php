<?php

namespace App\Services;

use App\Models\Company;
use App\Models\TradingAccount;

/**
 * Company-scoped usage counters for plan limits, and the tenant admin dashboard
 * usage panel (used vs limit per key, with a near-limit flag).
 */
class PlanLimitUsage
{
    public function __construct(private Company $company) {}

    public static function for(Company $company): self
    {
        return new self($company);
    }

    /**
     * Current company-wide usage for a limit key.
     */
    public function used(string $key): int
    {
        return match ($key) {
            'customers_max' => $this->company->users()->whereHasRole(['customer', 'user'])->count(),
            'managers_max' => $this->company->users()->whereHasRole(['manager', 'agent'])->count(),
            'admins_max' => $this->company->users()->whereHasRole(['admin', 'tenant_admin'])->count(),
            'leads_max' => $this->company->users()->whereHasRole(['lead'])->count(),
            'trading_accounts_max' => TradingAccount::query()
                ->where('company_id', $this->company->id)
                ->where('account_type', TradingAccount::TYPE_LIVE)
                ->count(),
            'practice_accounts_max' => TradingAccount::query()
                ->where('company_id', $this->company->id)
                ->where('account_type', TradingAccount::TYPE_PRACTICE)
                ->count(),
            default => 0,
        };
    }

    /**
     * Usage-panel rows for the keys listed in config('plan_limits.usage_panel').
     *
     * @return array<int, array{key:string,label:string,used:int,limit:int|null,unlimited:bool,remaining:int|null,near_limit:bool}>
     */
    public function panel(): array
    {
        $limits = PlanLimitService::for($this->company);
        $labels = config('plan_limits.labels', []);

        return collect(config('plan_limits.usage_panel', []))
            ->map(function (string $key) use ($limits, $labels) {
                $used = $this->used($key);
                $unlimited = $limits->isUnlimited($key);
                $cap = $unlimited ? null : $limits->get($key);

                return [
                    'key' => $key,
                    'label' => $labels[$key] ?? $key,
                    'used' => $used,
                    'limit' => $cap,
                    'unlimited' => $unlimited,
                    'remaining' => $limits->remaining($key, $used),
                    'near_limit' => ! $unlimited && $cap > 0 && $used >= (int) ceil($cap * 0.8),
                ];
            })
            ->all();
    }
}
