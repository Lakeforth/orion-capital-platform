<?php

use App\Models\Plan;
use Database\Seeders\PlanLimitSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed/refresh tenant-plan limits for Free/Standard/Pro/Enterprise so the caps
     * ship with the migration on deploy. Idempotent: updates limits when the plan
     * name already exists, otherwise creates the plan. Also a one-time backfill of
     * every existing company onto the Standard plan.
     */
    public function up(): void
    {
        $seeder = new PlanLimitSeeder;
        $seeder->run();
        $seeder->assignExistingCompaniesToStandard();
    }

    public function down(): void
    {
        Plan::query()
            ->whereIn('title', ['Free', 'Standard', 'Pro', 'Enterprise'])
            ->update(['limits' => null]);
    }
};
