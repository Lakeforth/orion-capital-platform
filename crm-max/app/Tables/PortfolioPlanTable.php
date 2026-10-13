<?php

namespace App\Tables;

use App\Models\InvestmentPlan;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class PortfolioPlanTable extends AbstractTable
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
        return InvestmentPlan::query()->where('investment_type', 'portfolio');
    }


    public function configure(SpladeTable $table)
    {
        $table
        ->withGlobalSearch(columns: ['title', 'category', 'type'])
        ->column('image', label: 'Image')
        ->column('title', sortable: true)
        ->column('price', sortable: true)
        ->column('category', sortable: true)
        ->column('percent_profit', sortable: true)
        ->column(key: 'period', sortable: true)
//        ->column('period_type', sortable: true)
        ->column('min_investment', label: 'Min Unit',sortable: true)
        ->column('max_investment', label: 'Max Unit', sortable: true)
            ->column('is_active', sortable: true)

        ->column('actions')
        ->selectFilter(
            key: 'category',
            options: portfolio_plan_categories()
        )
        ->selectFilter(
            key: 'type',
            options: [
                'long_term' => 'Long Term',
                'short_term' => 'Short Term',
                'mid_term' => 'Mid Term',
            ]
        )
        ->paginate(15);
    }
}
