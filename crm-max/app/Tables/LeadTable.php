<?php

namespace App\Tables;


use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\SpladeTable;

class LeadTable extends AbstractTable
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
        if(is_admin()){
            return User::query()->withCount('notes')->with('manager')->whereHasRole('lead');
        }else{
            return User::query()->withCount('notes')->with('manager')->whereHasRole('lead')->where('manager_id', auth()->id());
        }
    }

    /**
     * Configure the given SpladeTable.
     *
     * @param \ProtoneMedia\Splade\SpladeTable $table
     * @return void
     */


    public function configure(SpladeTable $table)
    {

        $desks = get_desks();
        $labels = get_labels();
        $status = get_lead_status();
        $teams = get_teams();
        $campaigns = get_campaigns();
        $sources = get_sources();


        $table
            ->withGlobalSearch(columns: ['first_name','last_name','phone','email'])
            ->defaultSort('created_at','desc')
            ->column(key: 'first_name', sortable: true)
            ->column(key: 'last_name', sortable: true)
            ->column(key: 'email', sortable: true)
            ->column(key: 'country', sortable: true)
            ->column(key: 'phone', sortable: true)
            ->column(key: 'ip_address', label: 'IP Address')
            ->column(key: 'device_type', label: 'Device', canBeHidden: false)
            ->column(key: 'browser_type', label: 'Browser', canBeHidden: false)
            ->column(key: 'manager_id', label: module_enabled('propfirm_enabled') ? 'Agent' : 'Manager', sortable: true)
            ->column(key: 'notes_count', label: 'Notes', sortable: true)
            ->column(key: 'status', sortable: true)
            ->column(key: 'source', sortable: true)
            ->column(key: 'campaign', sortable: true)
            ->column(key: 'ftd', sortable: true, hidden: false)
            ->column(key: 'created_by', sortable: true, label: 'Affiliate')
            ->column(key: 'created_at', sortable: true, hidden: false)


            ->selectFilter(
                key :'status',
                options : array_combine($status, $status),
                label: 'Status',
                noFilterOptionLabel: 'All Status'
            )
            ->selectFilter(
                key :'label',
                options : array_combine($labels, $labels),
                label: 'Label',
                noFilterOptionLabel: 'All Labels'
            )
            ->selectFilter(
                key :'team',
                options : array_combine($teams, $teams),
                label: 'Team',
                noFilterOptionLabel: 'All Teams'
            )
            ->selectFilter(
                key :'desk',
                options : array_combine($desks, $desks),
                label: 'Desk',
                noFilterOptionLabel: 'All Desks'
            )

            ->selectFilter(
                key :'source',
                options : array_combine($sources, $sources),
                label: 'Source',
                noFilterOptionLabel: 'All Source'
            )

            ->selectFilter(
                key :'campaign',
                options : array_combine($campaigns, $campaigns),
                label: 'Campaign',
                noFilterOptionLabel: 'All Campaigns'
            )


            ->bulkAction(
                label: 'Convert to Customers',
                each: fn (User $user) => null,
                before: function (array $ids) {
                    $service = app(UserService::class);
                    $results = $service->batchConvertLeadsToCustomers($ids);

                    Toast::info("{$results['success_count']} leads converted successfully. {$results['error_count']} failed.");
                    return false;
                },
                confirm: 'Convert to Customers',
                confirmText: 'Are you sure you want to convert these leads to customers?',
                confirmButton: 'Yes, Convert',
                cancelButton: 'Cancel'
            );

        if (can_delete_action()) {
            $table->bulkAction(
                label: 'Delete Leads',
                each: fn (User $user) => $user->delete(),
                confirm: 'Delete Leads',
                confirmText: 'Are you sure you want to proceed?',
                confirmButton: 'Yes',
                cancelButton: 'No!',
            );
        }

        $table
            ->column('actions')
            ->column('convert', hidden: false)
            ->paginate(10);
    }

}
