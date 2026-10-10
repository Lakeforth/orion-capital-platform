<?php

namespace App\Http\Controllers\Admin;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Top bar search: leads, affiliates, agents, managers and transactions matching one query. */
class LeadMaxSearchController extends LeadMaxController
{
    public const LIMIT = 8;

    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));
        $groups = [];
        if (mb_strlen($q) >= 2) {
            $people = fn (Builder $query) => $this->matchPeople($query, $q)->orderBy('first_name')->limit(self::LIMIT + 1)->get();
            $groups = [
                'leads' => $this->matchPeople($this->leadQuery(), $q)->latest()->limit(self::LIMIT + 1)->get(),
                'affiliates' => $people(User::query()->whereHasRole('affiliate')),
                'agents' => $people(User::query()->whereHasRole('manager')),
                'managers' => is_admin() ? $people(User::query()->whereHasRole(['admin', 'tenant_admin'])) : collect(),
                'transactions' => $this->transactions($q),
            ];
        }

        return view('admin.leadmax.search', [
            ...$this->common($request, 'search'),
            'q' => $q,
            'groups' => $groups,
            'total' => collect($groups)->sum(fn ($rows) => min($rows->count(), self::LIMIT)),
        ]);
    }

    private function matchPeople(Builder $query, string $q): Builder
    {
        $fullName = $query->getConnection()->getDriverName() === 'sqlite' ? "(first_name || ' ' || last_name)" : "CONCAT(first_name, ' ', last_name)";

        return $query->where(fn ($w) => $w->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")
            ->orWhereRaw("$fullName like ?", ["%{$q}%"])->orWhere('email', 'like', "%{$q}%")
            ->orWhere('phone', 'like', "%{$q}%")->orWhere('account_id', 'like', "%{$q}%"));
    }

    /** Deposits and withdrawals of the viewer's leads, by transaction or order id, or by the lead. */
    private function transactions(string $q)
    {
        $leads = $this->matchPeople($this->leadQuery(), $q)->select('id');

        return Transaction::query()->with('user:id,first_name,last_name,account_id')
            ->whereIn('type', [...self::DEPOSIT_TYPES, ...self::WITHDRAWAL_TYPES])
            ->whereIn('user_id', $this->leadQuery()->select('id'))
            ->where(fn ($w) => $w->where('id', 'like', "%{$q}%")->orWhere('order_id', 'like', "%{$q}%")->orWhereIn('user_id', $leads))
            ->latest()->limit(self::LIMIT + 1)->get();
    }
}
