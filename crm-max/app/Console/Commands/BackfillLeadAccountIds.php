<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\LeadMax\AccountNumber;
use Illuminate\Console\Command;

/**
 * Give an account number to leads created before AccountNumber existed.
 *
 * Those leads rendered as eight characters of their UUID wherever a Lead ID was shown. New
 * leads get a number on creation; this is for the ones already in the table.
 */
class BackfillLeadAccountIds extends Command
{
    protected $signature = 'leadmax:backfill-account-ids
                            {--company= : Limit to one company id}
                            {--dry-run : List what would change without writing}';

    protected $description = 'Assign account numbers to leads that have none';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $leads = User::query()
            ->whereHasRole('lead')
            ->when($this->option('company'), fn ($q, $id) => $q->where('company_id', $id))
            ->where(fn ($q) => $q->whereNull('account_id')->orWhere('account_id', ''))
            // Oldest first, so numbering follows the order the leads arrived in.
            ->orderBy('created_at')
            ->get();

        if ($leads->isEmpty()) {
            $this->info('Every lead already has an account number.');

            return self::SUCCESS;
        }

        $this->info(($dry ? 'Would assign' : 'Assigning').' account numbers to '.$leads->count().' lead(s).');
        $rows = [];

        foreach ($leads as $lead) {
            if ($dry) {
                // Only a preview: without writing, every row would be shown the same next
                // number, so say where numbering starts rather than inventing a column of it.
                $rows[] = [$lead->company_id, $lead->name, $lead->created_at, 'next from '.AccountNumber::next((int) $lead->company_id)];

                continue;
            }

            AccountNumber::ensure($lead);
            $rows[] = [$lead->company_id, $lead->name, $lead->created_at, $lead->fresh()->account_id ?: 'FAILED'];
        }

        $this->table(['Company', 'Lead', 'Created', 'Account ID'], $rows);

        $failed = collect($rows)->where(3, 'FAILED')->count();
        if ($failed) {
            $this->error("{$failed} lead(s) could not be given a number.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
