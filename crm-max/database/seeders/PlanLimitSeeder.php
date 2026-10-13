<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds tenant-plan limits for the four subscription tiers. Idempotent and keyed
 * by plan title: if a plan with the name already exists its `limits` are updated
 * (all rows of that title, e.g. monthly + yearly), otherwise the plan is created.
 * Re-runnable anytime: `php artisan db:seed --class=PlanLimitSeeder`.
 */
class PlanLimitSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tiers() as $tier) {
            $existing = Plan::query()->where('title', $tier['title'])->get();

            if ($existing->isNotEmpty()) {
                // Name exists -> only update the limits (leave price/features/etc. intact).
                $existing->each(function (Plan $plan) use ($tier) {
                    $plan->limits = $tier['limits'];
                    $plan->save();
                });

                continue;
            }

            Plan::create([
                'title' => $tier['title'],
                'price' => $tier['price'],
                'interval' => 'monthly',
                'period' => 1,
                'color' => '#E65455',
                'type' => 'brokerage',
                'description' => $tier['description'],
                'limits' => $tier['limits'],
            ]);
        }
    }

    /**
     * One-time backfill: put every existing company on the Standard plan.
     *
     * Intentionally NOT called from run() so a repeated `db:seed` never reassigns
     * companies that have since upgraded/downgraded. The seed migration invokes
     * this once on deploy. Returns the number of companies updated.
     */
    public function assignExistingCompaniesToStandard(): int
    {
        $standard = Plan::query()
            ->where('title', 'Standard')
            ->orderBy('price')
            ->first();

        if (! $standard) {
            return 0;
        }

        // Mass update (no model events) so it is safe and fast at deploy time.
        // Cached tenant lookups refresh within the hour; flush cache post-deploy
        // if you need the caps to take effect immediately.
        return Company::query()->update([
            'plan_id' => $standard->id,
            'subscription_type' => 'Standard',
        ]);
    }

    /**
     * @return array<int, array{title:string,price:float,description:string,limits:array<string,int>}>
     */
    private function tiers(): array
    {
        // Enterprise is unlimited across every registered limit key.
        $unlimited = array_fill_keys(array_keys(config('plan_limits.defaults', [])), -1);

        return [
            [
                'title' => 'Free',
                'price' => 0,
                'description' => 'Starter plan for new trading platforms.',
                'limits' => [
                    'open_trades_max' => 3,
                    'open_trades_per_account_max' => 2,
                    'trade_max_duration_seconds' => 180,
                    'customers_max' => 25,
                    'managers_max' => 2,
                    'admins_max' => 1,
                    'leads_max' => 100,
                    'trading_accounts_max' => 1,
                    'practice_accounts_max' => 2,
                    'daily_trades_max' => 10,
                ],
            ],
            [
                'title' => 'Standard',
                'price' => 49,
                'description' => 'For individual tactical analysts.',
                'limits' => [
                    'open_trades_max' => 20,
                    'open_trades_per_account_max' => 10,
                    'trade_max_duration_seconds' => -1,
                    'customers_max' => 50,
                    'managers_max' => 5,
                    'admins_max' => 1,
                    'leads_max' => 2000,
                    'trading_accounts_max' => 3,
                    'practice_accounts_max' => 5,
                    'daily_trades_max' => 200,
                ],
            ],
            [
                'title' => 'Pro',
                'price' => 149,
                'description' => 'For institutional-grade intelligence.',
                'limits' => [
                    'open_trades_max' => 100,
                    'open_trades_per_account_max' => 50,
                    'trade_max_duration_seconds' => -1,
                    'customers_max' => 2000,
                    'managers_max' => 50,
                    'admins_max' => 10,
                    'leads_max' => 20000,
                    'trading_accounts_max' => 10,
                    'practice_accounts_max' => 10,
                    'daily_trades_max' => -1,
                ],
            ],
            [
                'title' => 'Enterprise',
                'price' => 499,
                'description' => 'Custom multi-infrastructure.',
                'limits' => $unlimited,
            ],
        ];
    }
}
