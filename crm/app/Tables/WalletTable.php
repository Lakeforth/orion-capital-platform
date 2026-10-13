<?php

namespace App\Tables;

use App\Models\Wallet;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class WalletTable extends AbstractTable
{
    /**
     * Create a new instance.
     *
     * @return void
     */
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

    public function for()
    {
        return Wallet::query()->where('user_id', $this->user_id);
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->defaultSort('created_at','desc')
            ->column('type', sortable: false, canBeHidden: false)
            ->column('crypto', sortable: false, canBeHidden: false)
            ->column('crypto_network', sortable: false, canBeHidden: false)
            ->column('address', sortable: false, canBeHidden: false)
            ->column('balance', sortable: false, canBeHidden: false)
//            ->column('status', sortable: false, canBeHidden: false)
            ->column('created_at', sortable: false, canBeHidden: false)
            ->column('action', canBeHidden: false);
//            ->column('', sortable: true);

            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
