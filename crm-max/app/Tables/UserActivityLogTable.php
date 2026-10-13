<?php

namespace App\Tables;


use App\Models\ActivityLog;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class UserActivityLogTable extends AbstractTable
{
    public string $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }


    public function authorize(Request $request)
    {
        return true;
    }


    public function for()
    {
        return ActivityLog::query()->where('user_id', $this->user_id);
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->column('type', canBeHidden: false)
            ->column('title', canBeHidden: false)
            ->column('info', label: 'Detail', canBeHidden: false)
            ->column('created_at',  canBeHidden: false);

            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
