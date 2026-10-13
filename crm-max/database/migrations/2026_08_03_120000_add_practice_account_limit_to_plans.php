<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Backfill the practice (demo) account cap onto the four seeded tiers.
     *
     * Deliberately additive rather than re-running PlanLimitSeeder: the seeder
     * replaces a plan's whole limits array, which would revert any cap a
     * superadmin has since tuned through the plan editor. Plans that already
     * carry the key are left alone, and custom-titled plans fall through to the
     * registry default (-1, unlimited) with no row touched.
     */
    public function up(): void
    {
        $tierDefaults = [
            'Free' => 2,
            'Standard' => 5,
            'Pro' => 10,
            'Enterprise' => -1,
        ];

        Plan::query()
            ->whereIn('title', array_keys($tierDefaults))
            ->get()
            ->each(function (Plan $plan) use ($tierDefaults) {
                $limits = is_array($plan->limits) ? $plan->limits : [];

                if (array_key_exists('practice_accounts_max', $limits)) {
                    return;
                }

                $limits['practice_accounts_max'] = $tierDefaults[$plan->title];

                $plan->limits = $limits;
                $plan->save();
            });
    }

    public function down(): void
    {
        Plan::query()
            ->whereNotNull('limits')
            ->get()
            ->each(function (Plan $plan) {
                $limits = is_array($plan->limits) ? $plan->limits : [];

                if (! array_key_exists('practice_accounts_max', $limits)) {
                    return;
                }

                unset($limits['practice_accounts_max']);

                $plan->limits = $limits;
                $plan->save();
            });
    }
};
