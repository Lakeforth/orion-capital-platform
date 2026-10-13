<?php

namespace App\Tables;

use App\Models\Plan;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class SubscriptionPlanTable extends AbstractTable
{
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
        return Plan::query()->withCount('companies');
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['id'])
            ->column('title', sortable: true)
            ->column('price', sortable: true)
            ->column('interval', sortable: true)
            ->column('period', sortable: true)
            ->column('companies_count', 'Companies', sortable: true)
            ->column('color', sortable: true)
            ->column('actions', label: 'Actions')
            ->paginate(10);
    }
}
