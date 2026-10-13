<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Collapse duplicate subscription plans that share a title down to one canonical
 * row (create-or-update-by-name intent). Any company pointing at a removed row is
 * repointed to the survivor first, so no tenant is left on a deleted plan.
 *
 * Dry-run by default; pass --force to apply.
 */
class DedupePlans extends Command
{
    protected $signature = 'plans:dedupe {--force : Apply changes (default is a dry-run preview)}';

    protected $description = 'Collapse duplicate plans by title, keeping one canonical row and repointing companies.';

    public function handle(): int
    {
        $dryRun = ! $this->option('force');

        $groups = Plan::query()
            ->orderBy('created_at')
            ->get()
            ->groupBy('title');

        $removed = 0;
        $repointed = 0;

        foreach ($groups as $title => $plans) {
            if ($plans->count() < 2) {
                continue;
            }

            $survivor = $this->pickSurvivor($plans);
            $losers = $plans->reject(fn (Plan $plan) => $plan->id === $survivor->id);

            foreach ($losers as $loser) {
                $affected = Company::query()->where('plan_id', $loser->id)->count();

                $this->line(sprintf(
                    '%s"%s": drop %s (%s) — repoint %d company(ies) → keep %s (%s)',
                    $dryRun ? '[dry-run] ' : '',
                    $title,
                    $loser->id,
                    $loser->interval ?? 'n/a',
                    $affected,
                    $survivor->id,
                    $survivor->interval ?? 'n/a',
                ));

                if (! $dryRun) {
                    DB::transaction(function () use ($loser, $survivor) {
                        Company::query()
                            ->where('plan_id', $loser->id)
                            ->update([
                                'plan_id' => $survivor->id,
                                'subscription_type' => $survivor->title,
                            ]);

                        $loser->delete();
                    });
                }

                $repointed += $affected;
                $removed++;
            }
        }

        $this->info(sprintf(
            '%sDuplicate rows: %d; companies repointed: %d.%s',
            $dryRun ? '[dry-run] ' : '',
            $removed,
            $repointed,
            $dryRun ? ' Re-run with --force to apply.' : ' Done.',
        ));

        return self::SUCCESS;
    }

    /**
     * Prefer a row that already carries limits, then the monthly interval, then the
     * oldest — so the canonical plan keeps its caps and monthly billing.
     */
    private function pickSurvivor($plans): Plan
    {
        // Single closure returning a composite key; PHP compares arrays element-wise,
        // giving a stable multi-key ascending sort (has-limits → monthly → oldest).
        return $plans->sortBy(fn (Plan $plan) => [
            empty($plan->limits) ? 1 : 0,
            $plan->interval === 'monthly' ? 0 : 1,
            $plan->created_at?->getTimestamp() ?? 0,
        ])->first();
    }
}
