<?php

namespace App\Tables;

use App\Models\Data;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class SuperCustomerPlanFeatureTable extends AbstractTable
{
    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        return Data::query()
            ->where('type', 'plan')
            ->whereNull('company_id')
            ->orderBy('name');
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['name'])
            ->column('name', sortable: true)
            ->column('slug', sortable: true)
            ->column('is_active', label: 'Status', sortable: true)
            ->column('actions')
            ->paginate(10);
    }
}
