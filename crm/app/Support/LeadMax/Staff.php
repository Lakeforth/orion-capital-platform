<?php

namespace App\Support\LeadMax;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The two tiers of sales staff, and who reports to whom.
 *
 * Before this, there were no tiers. The Agents page and the Managers page ran the same query
 * (whereHasRole('manager')), "Add Agent" and "Add Manager" both called addRole('manager'), and
 * no staff member had a reports-to link. They were two menus over one set of people.
 *
 * The split is marked with the `agent` role, which already existed:
 *
 *   - An agent carries `manager` AND `agent`. It keeps `manager` so that nothing about its
 *     permissions changes -- RestrictedManagerAccess and the rest read that role, and taking
 *     it away to re-badge someone would quietly alter what they can do.
 *   - A manager carries `manager` only.
 *
 * Reports-to is users.manager_id, the column a lead already uses to point at its agent. The
 * meaning is the same one level up: "who owns me". Every existing query that walks it is
 * scoped to the lead role, so an agent pointing at a manager does not leak into lead counts.
 */
class Staff
{
    /** Agents: the people who actually work leads. */
    public static function agents(): Builder
    {
        $query = User::query()->whereHasRole('agent');

        // A company that has never been split has everyone on `manager` and nobody on
        // `agent`. Treat that whole set as agents rather than showing an empty page, which
        // is also what they were before this existed.
        if (! self::isSplit()) {
            return User::query()->whereHasRole('manager');
        }

        return $query;
    }

    /** Managers: the tier above. Empty until someone is put there. */
    public static function managers(): Builder
    {
        if (! self::isSplit()) {
            // Nobody has been marked as an agent yet, so nobody is above anyone.
            return User::query()->whereRaw('1 = 0');
        }

        return User::query()
            ->whereHasRole('manager')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'agent'));
    }

    /** Leads belonging to a manager: their own, plus every one of their agents'. */
    public static function leadsUnderManager(string $managerId): Builder
    {
        $agentIds = self::agents()->where('manager_id', $managerId)->pluck('id')->all();

        return User::query()
            ->whereHasRole('lead')
            ->whereIn('manager_id', [$managerId, ...$agentIds]);
    }

    /** Has this company been split into the two tiers yet? */
    public static function isSplit(): bool
    {
        return User::query()->whereHasRole('agent')->exists();
    }
}
