<?php

namespace App\Tables;

use App\Models\Transaction;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class UserTransactionTable extends AbstractTable
{
    public string $type;
    public function __construct($type)
    {
        $this->type = $type;
    }


    public function authorize(Request $request)
    {
        return true;
    }


    public function for()
    {
        return Transaction::where('user_id', auth()->id());
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->column('amount', canBeHidden: false, sortable: true)
            ->column('details', canBeHidden: false, sortable: true)
            ->column('status', canBeHidden: false, sortable: true)
            ->paginate(10);

        // ->searchInput()
        // ->selectFilter()
        // ->withGlobalSearch()

        // ->bulkAction()
        // ->export()
    }
}
