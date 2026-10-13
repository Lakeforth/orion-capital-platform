<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Clear manager_id values that point at a user who no longer exists.
 *
 * Such a lead shows "Unassigned" in the Agent column, because the manager relation resolves to
 * null, while the Unassigned filter matches on a null manager_id and skips it — so it reads as
 * unassigned everywhere but cannot be found as unassigned.
 *
 * Deleting a user now releases their leads (see User::boot), so this is a one-off for rows that
 * predate that.
 */
class ReleaseDanglingAgents extends Command
{
    protected $signature = 'leadmax:release-dangling-agents
                            {--company= : Limit to one company id}
                            {--dry-run : List what would change without writing}';

    protected $description = 'Clear lead manager_id values whose agent no longer exists';

    public function handle(): int
    {
        $dangling = DB::table('users as l')
            ->leftJoin('users as m', 'l.manager_id', '=', 'm.id')
            ->whereNotNull('l.manager_id')
            ->whereNull('m.id')
            ->when($this->option('company'), fn ($q, $id) => $q->where('l.company_id', $id))
            ->select('l.id', 'l.company_id', 'l.account_id', 'l.first_name', 'l.last_name', 'l.manager_id')
            ->get();

        if ($dangling->isEmpty()) {
            $this->info('No lead points at a missing agent.');

            return self::SUCCESS;
        }

        $this->table(
            ['Company', 'Account ID', 'Lead', 'Missing agent'],
            $dangling->map(fn ($r) => [
                $r->company_id,
                $r->account_id ?: '--',
                trim($r->first_name.' '.$r->last_name),
                $r->manager_id,
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run: '.$dangling->count().' lead(s) would be released. Nothing written.');

            return self::SUCCESS;
        }

        $released = DB::table('users')->whereIn('id', $dangling->pluck('id'))->update(['manager_id' => null]);
        $this->info("Released {$released} lead(s); they are now genuinely unassigned.");

        return self::SUCCESS;
    }
}
