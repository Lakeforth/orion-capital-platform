<?php

namespace App\Tables;


use App\Models\Investment;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class PortfolioInvTable extends AbstractTable
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
        $query =  Investment::query()->where('investment_type', 'portfolio');

        return $query;

    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['title', 'status'])

//            ->column('id', sortable: true)
//            ->column('user.name', label: 'User', sortable: true)
            ->column('user.name', label: 'User')
            ->column('title', label: 'Investment Plan', sortable: true)
            ->column('amount', label: 'Investment Amount', sortable: true)
            ->column('start_date', sortable: true)
            ->column('end_date', sortable: true)
            ->column('total_profit', label: 'Expected Profit', sortable: true)
            ->column('profit_earned', label: 'Current Profit', sortable: true)
            ->column('status', sortable: true)
            ->column('units', label: 'Units', sortable: true)

            ->column('created_at', label: 'Date', sortable: true)
            ->column('actions')
            ->selectFilter(
                key :'status',
                options :  [
                    'completed' => 'Completed',
                    'active' => 'Active',
                ],
                label: 'Status',
                noFilterOptionLabel: 'All Investment'
            )
            ->paginate(15);

        // ->searchInput()
        // ->selectFilter()
        // ->withGlobalSearch()

        // ->bulkAction()
        // ->export()
    }
}
