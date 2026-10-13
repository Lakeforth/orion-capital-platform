<?php

namespace App\Tables;

use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class ManagerTable extends AbstractTable
{
    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        $propfirmEnabled = filter_var(company_settings('propfirm_enabled', false), FILTER_VALIDATE_BOOLEAN);

        $query = User::query()->whereHasRole('manager');

        // Prop Firm's hierarchy is two-tier (Manager -> Agent -> Customer), so
        // a manager's "customers" are their agents' customers, not direct
        // reports -- brokerage stays on the original direct-report counts.
        if ($propfirmEnabled) {
            $query->withCount([
                'users as agents_count' => fn ($q) => $q->whereHasRole('agent'),
                'agentCustomers as customers_count' => fn ($q) => $q->whereHasRole(['customer', 'user']),
            ]);
        } else {
            $query->withCount([
                'users as customers_count' => fn ($q) => $q->whereHasRole(['customer', 'user']),
                'users as leads_count' => fn ($q) => $q->whereHasRole('lead'),
            ]);
        }

        if (! is_admin()) {
            $query->where('manager_id', auth()->id());
        }

        return $query;
    }

    public function configure(SpladeTable $table)
    {
        $propfirmEnabled = filter_var(company_settings('propfirm_enabled', false), FILTER_VALIDATE_BOOLEAN);

        $table
            ->withGlobalSearch(columns: ['first_name', 'last_name', 'phone', 'email'])
            ->defaultSort('created_at', 'desc')
            ->column(key: 'first_name', label: 'Name', sortable: true)
            ->column(key: 'phone', sortable: true)
            ->column(key: 'email', sortable: true);

        if ($propfirmEnabled) {
            $table->column(key: 'agents_count', label: 'Agents', sortable: true);
        } else {
            $table->column(key: 'leads_count', label: 'Leads', sortable: true);
        }

        $table
            ->column(key: 'customers_count', label: 'Customers', sortable: true)
            ->column(key: 'last_seen', sortable: true)
            ->column(key: 'created_at', sortable: true, hidden: true)
            ->column('actions')
            ->paginate(10);
    }
}
