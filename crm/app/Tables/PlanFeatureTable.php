<?php

namespace App\Tables;

use App\Models\GlobalData;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class PlanFeatureTable extends AbstractTable
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
        return GlobalData::query()->where('type', 'plan_features');
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['name', 'data_type'])
            ->column('name', sortable: true)
            ->column('data_type', label: 'Type', sortable: true)
            ->column('slug', sortable: true)
            ->column('actions')
            ->paginate(10);
    }
}
