<?php

namespace App\Tables;

use App\Models\AccountPlan;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class PlanTable extends AbstractTable
{
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
        return AccountPlan::query();
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['id'])
            ->column('title', sortable: true)
            ->column('icon', sortable: true)
            ->column('leverage', sortable: true)
            ->when(
                module_enabled('expert_advisor'),
                fn ($table) => $table->column('expert_advisors', sortable: true)
            )
            ->column('min_deposit', sortable: true)
            ->when(
                module_enabled('enable_plan_price'),
                fn ($table) => $table->column('price', sortable: true)
            )
            ->column('actions', label: 'Actions')
            ->paginate(10);

        // ->searchInput()
        // ->selectFilter()
        // ->withGlobalSearch()

        // ->bulkAction()
        // ->export()
    }
}
