<?php

namespace App\Tables;


use App\Models\Asset;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class AssetMiniTable extends AbstractTable
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
        return Asset::query();
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['name', 'sym'])
            ->column('name', sortable: true)
            ->column('rate',sortable: true, as: fn($value) => number_format($value, 2))
            ->paginate(15);
    }
}
