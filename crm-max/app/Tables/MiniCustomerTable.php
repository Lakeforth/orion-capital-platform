<?php

namespace App\Tables;

use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class MiniCustomerTable extends AbstractTable
{
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the user is authorized to perform bulk actions and exports.
     *
     * @return bool
     */
    public function authorize(Request $request)
    {
        return true;
    }

    /**
     * The resource or query builder.
     *
     * @return mixed
     */
    public function for()
    {
        if (is_admin()) {
            return User::query()
                ->whereHasRole(['customer', 'user'])
                ->with(['affiliate', 'manager', 'tradingAccounts'])
                ->withCount([
                    'trades as open_trades_count' => fn ($query) => $query->where('status', 0),
                    'trades as closed_trades_count' => fn ($query) => $query->where('status', 1),
                    'notes',
                ]);
        } else {
            return User::query()
                ->where('manager_id', auth()->id())
                ->whereHasRole(['customer', 'user'])
                ->with(['affiliate', 'manager', 'tradingAccounts'])
                ->withCount([
                    'trades as open_trades_count' => fn ($query) => $query->where('status', 0),
                    'trades as closed_trades_count' => fn ($query) => $query->where('status', 1),
                    'notes',
                ]);
        }
        //        return User::query()->whereHasRole('user')->withCount(['wallets','transactions']);
    }

    public function configure(SpladeTable $table)
    {
        $status = get_status();
        $campaigns = get_campaigns();
        $sources = get_sources();

        $table
            ->withGlobalSearch(columns: ['first_name', 'phone', 'last_name', 'email'])
            ->defaultSort('created_at', 'desc')
            ->column(key: 'first_name', label: 'Customer', sortable: true)
            ->column(key: 'account_id', label: 'Identity', sortable: true, hidden: true)
            ->column(key: 'email', label: 'Contact', sortable: true)
            ->column(key: 'status', label: 'Status', sortable: true)
            ->column(key: 'verification_status', label: 'KYC', sortable: true)
            ->column(key: 'manager_id', label: module_enabled('propfirm_enabled') ? 'Agent' : 'Manager', sortable: false)
            ->column(key: 'source', label: 'Source', sortable: true)
            ->column(key: 'campaign', label: 'Campaign', sortable: true, hidden: true)
            ->column(key: 'trades', label: "Trading\nAccounts", sortable: false)
            ->column(key: 'balance', label: "Financial\nSummary", sortable: true)
            ->column(key: 'created_at', label: "Last\nActivity", sortable: true)
            ->column(key: 'actions', label: 'Actions')
            ->selectFilter(
                key : 'status',
                options : array_combine($status, $status),
                label: 'Status',
                noFilterOptionLabel: 'All Status'
            )
            ->selectFilter(
                key : 'source',
                options : array_combine($sources, $sources),
                label: 'Source',
                noFilterOptionLabel: 'All Source'
            )
            ->selectFilter(
                key : 'campaign',
                options : array_combine($campaigns, $campaigns),
                label: 'Campaign',
                noFilterOptionLabel: 'All Campaigns'
            );

        $table
            ->paginate(10);
    }
}
