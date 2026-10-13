<?php

namespace App\Tables;

use App\Models\Transaction;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class TransactionTable extends AbstractTable
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
        return Transaction::query()
            ->where('user_id', $this->user_id);
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->column('amount', canBeHidden: false)
            ->column('type', canBeHidden: false)
            ->column('method', canBeHidden: false)
            ->column('status', canBeHidden: false)
            ->column('details' )
            ->column('created_at', canBeHidden: false)
            ->selectFilter(
                key: 'type',
                options: [
                    'd' => 'Deposit',
                    'withdrawal' => 'Withdrawal',
                ]
            )
            ->selectFilter(
                key: 'status',
                options: [
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                ]
            )
            ->paginate(20);

            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
