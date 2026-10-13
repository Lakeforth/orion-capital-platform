<?php

namespace App\Tables;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class CompanyTable extends AbstractTable
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
        return Company::query()
            ->with('plan')
            ->withCount(['customers', 'admins', 'domains']);
    }

    public function configure(SpladeTable $table)
    {
        $planOptions = Plan::query()
            ->orderBy('title')
            ->pluck('title', 'id')
            ->toArray();

        $table
            ->withGlobalSearch(columns: ['id'])
            ->column('name', sortable: true)
            ->column('slug', sortable: true)
            ->column('email', sortable: true)
            ->column('phone', sortable: true)
            ->column('plan_title', 'Plan')
            ->column('is_active', 'Active', sortable: true)
            ->column('domains_count', 'Domains', sortable: true)
            ->column('customers_count', 'Customers', sortable: true)
            ->column('admins_count', 'Admins', sortable: true)
            ->column('actions')
            ->selectFilter('plan_id', $planOptions, label: 'Plan')
            ->paginate(10);

        // ->searchInput()
        // ->selectFilter()
        // ->withGlobalSearch()

        // ->bulkAction()
        // ->export()
    }
}
