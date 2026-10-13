<?php

namespace App\Tables;

use App\Models\Investment;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class InvestmentTable extends AbstractTable
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


    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        return Investment::query()->where('investment_type', 'investment')->where('user_id', auth()->id());
    }

    public function configure(SpladeTable $table)
    {
        $table

//            ->column('id', sortable: true)
//            ->column('user.name', label: 'User', sortable: true)
//            ->column('account.name', label: 'Account', canBeHidden: false)
            ->column('issue', canBeHidden: false)
            ->column('start_date', sortable: true)
            ->column('end_date', sortable: true)
//            ->column('manager.name', label: 'Manager', canBeHidden: false)
            ->column('asset_class', sortable: true, canBeHidden: false)
            ->column('issue_type', canBeHidden: false)
            ->column('current_qty', label: 'Current Quantity', canBeHidden: false)
            ->column('currency', canBeHidden: false)
//            ->column('investment_plan.name', label: 'Plan', sortable: true)
            ->column('amount', label: 'Investment Amount', sortable: true, canBeHidden: false)
            ->column('latest_valuation', sortable: true, canBeHidden: false)
            ->column('latest_price_per_unit', label: 'Latest Price Per Unit/Share', sortable: true, canBeHidden: false)
            ->column('avg_price_per_unit', label: 'Avg Price Per Unit/Share', sortable: true, canBeHidden: false)
            ->column('status', canBeHidden: false)

//            ->column('currency')
//            ->column('actions')
            ->paginate(15);

            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
