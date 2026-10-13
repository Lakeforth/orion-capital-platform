<?php

namespace App\Tables;


use App\Models\ActivityLog;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class LogTable extends AbstractTable
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
        return ActivityLog::query()->with('user');
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['title'])
            ->defaultSort('created_at','desc')
            ->column('type', canBeHidden: false)
            ->column('user_id','User', canBeHidden: false)
            ->column('title', canBeHidden: false)
            ->column('info', label: 'Detail', canBeHidden: false)
            ->column('created_at',  canBeHidden: false)
            ->paginate(10);

        // ->searchInput()
        // ->selectFilter()
        // ->withGlobalSearch()

        // ->bulkAction()
        // ->export()
    }

}
