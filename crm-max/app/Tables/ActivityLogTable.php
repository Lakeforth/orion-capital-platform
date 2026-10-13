<?php

namespace App\Tables;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class ActivityLogTable extends AbstractTable
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
        return ActivityLog::query();
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['id'])
            ->column('title', canBeHidden: false)
            ->column('info', canBeHidden: false);


            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
