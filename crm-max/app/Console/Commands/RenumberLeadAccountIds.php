<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\LeadMax\AccountNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Renumber a company's leads to one clean, same-length sequence.
 *
 * The lead IDs on NCapital Pro grew in three different shapes -- 14859 from one import,
 * 9900012 from a seeder, 990260005 from the backfill -- which is what the client reported as
 * "some are longer than the others". This rewrites them to 000001, 000002, ... in registration
 * order.
 *
 * Only the lead role. The same column holds AGT-* codes for managers and letter codes for
 * affiliates, and a customer who was never a lead may hold something else again; none of those
 * are lead IDs and none are touched.
 *
 * A lead ID is a label, not a key -- every relation in the schema joins on users.id, a UUID --
 * so renumbering moves no foreign keys. It does change what an agent sees and may have quoted,
 * which is why every run writes an old -> new map before it commits.
 */
class RenumberLeadAccountIds extends Command
{
    protected $signature = 'leadmax:renumber-leads
        {--company= : Company id to renumber. Required; there is no "all tenants" mode on purpose.}
        {--apply : Write the changes. Without this the command only reports what it would do.}
        {--start=1 : First number in the new sequence.}';

    protected $description = 'Renumber a company\'s lead account IDs to one same-length sequence';

    public function handle(): int
    {
        $companyId = (int) $this->option('company');

        if ($companyId <= 0) {
            $this->error('Pass --company=<id>. Renumbering every tenant at once is not supported.');

            return self::FAILURE;
        }

        $leads = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereHas('roles', fn ($query) => $query->where('name', 'lead'))
            // Registration order, so the sequence reads as the order they arrived. id breaks
            // ties so two leads created in the same second cannot swap places between a dry
            // run and the real one.
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'company_id', 'account_id', 'first_name', 'last_name', 'created_at']);

        if ($leads->isEmpty()) {
            $this->warn("No leads found for company {$companyId}.");

            return self::SUCCESS;
        }

        $start = max(1, (int) $this->option('start'));
        $plan = [];
        $number = $start;

        foreach ($leads as $lead) {
            $new = AccountNumber::format($number);
            $plan[] = [
                'id' => $lead->id,
                'old' => (string) $lead->account_id,
                'new' => $new,
                'name' => trim($lead->first_name.' '.$lead->last_name),
                'created' => (string) $lead->created_at,
            ];
            $number++;
        }

        $changing = array_values(array_filter($plan, fn ($row) => $row['old'] !== $row['new']));

        $this->info("Company {$companyId}: {$leads->count()} leads, ".count($changing).' to change.');
        $this->table(
            ['Old', 'New', 'Name', 'Registered'],
            array_map(fn ($r) => [$r['old'] ?: '(none)', $r['new'], $r['name'], $r['created']], array_slice($changing, 0, 10))
        );

        if (count($changing) > 10) {
            $this->line('  ... and '.(count($changing) - 10).' more.');
        }

        // A number this company already uses outside the lead role would collide. The column
        // carries no unique index and cannot be given one, so it is checked here instead.
        $reserved = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'lead'))
            ->pluck('account_id')
            ->filter()
            ->map(fn ($value) => (string) $value)
            ->flip();

        $collisions = array_filter($plan, fn ($row) => $reserved->has($row['new']));

        if ($collisions !== []) {
            $this->error('Refusing to run: '.count($collisions).' of the new numbers are already held by a non-lead in this company.');
            foreach (array_slice($collisions, 0, 5) as $row) {
                $this->line("  {$row['new']} is taken");
            }

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->comment('Dry run. Re-run with --apply to write it.');

            return self::SUCCESS;
        }

        // The map is written before the transaction commits, so a run that is interrupted still
        // leaves behind what it was going to do.
        $path = storage_path('app/lead-renumber-'.$companyId.'-'.now()->format('Ymd-His').'.csv');
        $csv = "id,old_account_id,new_account_id,name,created_at\n";
        foreach ($plan as $row) {
            $csv .= sprintf("%s,%s,%s,\"%s\",%s\n", $row['id'], $row['old'], $row['new'], str_replace('"', '""', $row['name']), $row['created']);
        }
        file_put_contents($path, $csv);
        $this->info("Map written to {$path}");

        DB::transaction(function () use ($changing) {
            foreach ($changing as $row) {
                // Straight to the query builder: a model save would fire the update hooks
                // (welcome mail, broadcast) for what is a relabelling, not a change to the lead.
                DB::table('users')->where('id', $row['id'])->update(['account_id' => $row['new']]);
            }
        });

        $this->info('Renumbered '.count($changing).' leads. New leads continue from '.AccountNumber::next($companyId).'.');

        return self::SUCCESS;
    }
}
