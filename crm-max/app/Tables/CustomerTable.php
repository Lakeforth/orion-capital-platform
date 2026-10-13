<?php

namespace App\Tables;

use App\Models\User;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class CustomerTable extends AbstractTable
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

    public function for()
    {
        if (is_admin()) {
            return User::query()->whereHasRole(['customer', 'user'])->with(['affiliate', 'manager']);
        } else {
            return User::query()
                ->where('manager_id', auth()->id())
                ->whereHasRole(['customer', 'user'])
                ->with(['affiliate', 'manager']);
        }
        //        return User::query()->whereHasRole('user')->withCount(['wallets','transactions']);
    }

    public function configure(SpladeTable $table)
    {
        $desks = get_desks();
        $labels = get_labels();
        $status = get_lead_status();
        $teams = get_teams();
        $campaigns = get_campaigns();
        $sources = get_sources();

        $limited_actions = ! is_admin() && module_enabled('limited_actions');
        $can_see_balance = is_admin() || module_enabled('access_user_balance');

        $table
            ->withGlobalSearch(columns: ['first_name', 'phone', 'last_name', 'email'])
            ->defaultSort('created_at', 'desc')
            ->column(key: 'first_name', label: 'Name', sortable: true)
            ->column(key: 'last_name', label: 'Order Book', sortable: false)
            ->column(key: 'user_status', label: 'Status', sortable: true)
//            ->column(key: 'email', sortable: true)
//            ->column(key: 'plan_id', label : 'Plan', sortable: true)

            ->column(key: 'copy_trader', sortable: true);
        //            ->column(key: 'phone', sortable: true)

        if (is_admin()) {
            $table->column(key: 'manager_id', label: module_enabled('propfirm_enabled') ? 'Agent' : 'Manager', sortable: false);
        }
        if ($can_see_balance) {
            $table->column(key: 'balance', sortable: true);
        }

        $table->column(key: 'label', hidden: true, sortable: true)
            ->column(key: 'desk', hidden: true, sortable: true)
            ->column(key: 'status', hidden: true, sortable: true)
            ->column(key: 'source', hidden: true, sortable: true)
            ->column(key: 'campaign', hidden: true, sortable: true)

//            ->column(key: 'affiliate',  sortable: false, hidden: true)
//            ->column(key: 'balance', sortable: true)
//            ->column(key: 'last_online', sortable: true, hidden: true)
//            ->column(key: 'online', sortable: true, hidden: true)
            ->column(key: 'kyc', sortable: false)

            ->column(key: 'created_at', sortable: true, hidden: true)
            ->column(key: 'last_seen', sortable: true);

        //        $table->column(key: 'call', label: 'Call & Chat',  sortable: false);

        $table->column('actions');

        $table->selectFilter(
            key : 'status',
            options : array_combine($status, $status),
            label: 'Status',
            noFilterOptionLabel: 'All Status'
        )
            ->selectFilter(
                key : 'label',
                options : array_combine($labels, $labels),
                label: 'Label',
                noFilterOptionLabel: 'All Labels'
            )
            ->selectFilter(
                key : 'team',
                options : array_combine($teams, $teams),
                label: 'Team',
                noFilterOptionLabel: 'All Teams'
            )
            ->selectFilter(
                key : 'desk',
                options : array_combine($desks, $desks),
                label: 'Desk',
                noFilterOptionLabel: 'All Desks'
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
