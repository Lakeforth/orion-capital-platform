<?php

namespace App\Tables;


use App\Models\InvestmentAccount;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class MyInvAccountTable extends AbstractTable
{
    public string $user_id;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
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
        return InvestmentAccount::query()->where('user_id', $this->user_id);
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->column('name', label: 'Account Name', canBeHidden: false)
            ->column('type', canBeHidden: false, sortable: true)
            ->column('status', canBeHidden: false, sortable: true)
            ->column('created_at', label: 'Date Created', canBeHidden: false, sortable: true);

            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
