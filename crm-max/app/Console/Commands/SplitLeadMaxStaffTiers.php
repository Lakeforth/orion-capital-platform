<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Mark a company's existing sales staff as agents.
 *
 * Before the two tiers existed, everyone created through either "Add Agent" or "Add Manager"
 * got the `manager` role, and the Agents and Managers pages listed the same people. This gives
 * each of them the `agent` role as well, which is what now puts someone in the agent tier.
 *
 * Additive on purpose: `manager` is left in place, because that is the role the permission
 * checks read (RestrictedManagerAccess among them). Nobody gains or loses access -- the only
 * change is which of the two pages they appear on.
 *
 * Admins and superadmins are left alone: they are not sales staff, and an admin who also holds
 * `manager` should not turn up on the Agents page because of this.
 */
class SplitLeadMaxStaffTiers extends Command
{
    protected $signature = 'leadmax:split-staff-tiers
        {--company= : Company id. Required; there is no "all tenants" mode on purpose.}
        {--apply : Write the changes. Without this the command only reports.}';

    protected $description = 'Mark a company\'s existing sales staff as agents, so the manager tier starts empty';

    public function handle(): int
    {
        $companyId = (int) $this->option('company');

        if ($companyId <= 0) {
            $this->error('Pass --company=<id>.');

            return self::FAILURE;
        }

        $staff = User::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereHas('roles', fn ($query) => $query->where('name', 'manager'))
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['admin', 'superadmin', 'tenant_admin']))
            ->with('roles')
            ->orderBy('first_name')
            ->get();

        $needing = $staff->reject(fn (User $user) => $user->roles->pluck('name')->contains('agent'));

        $this->info("Company {$companyId}: {$staff->count()} sales staff, {$needing->count()} to mark as agents.");

        if ($needing->isEmpty()) {
            $this->comment('Nothing to do — they already carry the agent role.');

            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Roles now', 'Leads'],
            $needing->map(fn (User $user) => [
                trim($user->first_name.' '.$user->last_name),
                $user->roles->pluck('name')->implode(', '),
                User::withoutGlobalScopes()->where('manager_id', $user->id)->count(),
            ])->all()
        );

        if (! $this->option('apply')) {
            $this->comment('Dry run. Re-run with --apply to write it.');

            return self::SUCCESS;
        }

        foreach ($needing as $user) {
            $user->addRole('agent');
        }

        $this->info('Marked '.$needing->count().' as agents. The Managers page now starts empty, ready to be filled in.');

        return self::SUCCESS;
    }
}
