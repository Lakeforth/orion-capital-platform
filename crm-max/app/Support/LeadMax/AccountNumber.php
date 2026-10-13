<?php

namespace App\Support\LeadMax;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Account numbers for leads.
 *
 * `users.account_id` is a nullable string and nothing ever filled it for a lead: of the seven
 * code paths that create one (the New Lead dialog, the CSV/Drive import job, two API endpoints,
 * two survey endpoints and the seeders) only a seeder set it. Every other lead fell through to
 * the views' `account_id ?: substr($lead->id, 0, 8)` fallback and showed eight characters of a
 * UUID, which reads as a broken ID. A lead must never be identified by its UUID.
 *
 * Rather than patch each creation path -- which is how the gap appeared in the first place --
 * this hangs off Laratrust's `role.added` event, the one thing all seven paths do.
 */
class AccountNumber
{
    /**
     * First number issued to a company that has none yet.
     *
     * Seven digits so an account number never looks like a row count or a test value.
     */
    private const BASE = 1000000;

    /** Attempts before giving up; only a concurrent insert can take a number from under us. */
    private const ATTEMPTS = 5;

    /**
     * Minimum width, zero padded.
     *
     * The client asked for lead IDs that are all the same length: 000001, not a mix of 14859
     * and 990260005. Padding rather than a fixed width so a tenant already numbering above it
     * is left alone -- str_pad never shortens.
     */
    public const WIDTH = 6;

    /**
     * Give a lead an account number if it has none.
     *
     * Safe to call repeatedly and for any role -- it returns immediately unless this is a lead
     * that is actually missing one.
     */
    public static function ensure(User $user, int|string|null $roleId = null): void
    {
        if (filled($user->account_id) || blank($user->company_id)) {
            return;
        }

        // Resolve the role by id rather than ->hasRole(), which reads a cache that the event
        // fires too early to have refreshed.
        if ($roleId !== null && Role::query()->whereKey($roleId)->value('name') !== 'lead') {
            return;
        }
        if ($roleId === null && ! $user->hasRole('lead')) {
            return;
        }

        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $candidate = self::next((int) $user->company_id);

            // users.account_id carries no unique index, and cannot be given one: other tenants
            // store email addresses in it and already hold duplicates. So check before writing.
            $taken = DB::table('users')
                ->where('company_id', $user->company_id)
                ->where('account_id', $candidate)
                ->exists();

            if ($taken) {
                continue;
            }

            $user->account_id = $candidate;
            // saveQuietly: this runs inside a model event, and a normal save would re-enter the
            // updating hooks (welcome mail, broadcast) for what is really part of the insert.
            $user->saveQuietly();

            return;
        }
    }

    /**
     * The next account number for a company: one past its highest existing numeric one.
     *
     * Scoped per company because numbering is per tenant, and restricted to numeric values
     * because other tenants keep non-numeric strings in this column.
     */
    /** A number as it is stored: zero padded to WIDTH, longer numbers untouched. */
    public static function format(int $number): string
    {
        return str_pad((string) $number, self::WIDTH, '0', STR_PAD_LEFT);
    }

    public static function next(int $companyId): string
    {
        // Filtered in PHP rather than with a SQL REGEXP: REGEXP is MySQL-only and the suite runs
        // on SQLite. MAX(CAST(...)) would also be wrong here — other tenants keep email
        // addresses in this column, and casting those yields 0, or worse a leading digit.
        $highest = DB::table('users')
            ->where('company_id', $companyId)
            ->whereNotNull('account_id')
            ->where('account_id', '<>', '')
            ->pluck('account_id')
            ->filter(fn ($value) => ctype_digit((string) $value))
            ->map(fn ($value) => (int) $value)
            ->max() ?? 0;

        // The base is a starting point for a company that has never issued one, not a floor:
        // a tenant already numbering in the thousands keeps counting from where it is.
        return self::format($highest > 0 ? $highest + 1 : self::BASE + 1);
    }
}
