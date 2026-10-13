<?php

namespace App\Tables;

use App\Models\Wallet;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class AllWalletTable extends AbstractTable
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



    public function for()
    {
        return Wallet::query();
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['type','crypto','crypto_network','address'])
            ->defaultSort('created_at','desc')
            ->column('barcode', sortable: true, canBeHidden: false)
            ->column('type', sortable: true, canBeHidden: false)
            ->column('crypto', sortable: true, canBeHidden: false)
            ->column('crypto_network', sortable: true)
            ->column('address', sortable: true)
//            ->column('balance', label: 'Cumulative Balance', sortable: false, canBeHidden: false)
//            ->column('is_general', sortable: true, canBeHidden: false)
            ->column('is_active', sortable: true, canBeHidden: false)
//            ->column('status', sortable: true, canBeHidden: false)
            ->column('created_at', sortable: true)
            ->column('actions', canBeHidden: false);
//            ->column('', sortable: true);

        // ->searchInput()
        // ->selectFilter()
        // ->withGlobalSearch()

        // ->bulkAction()
        // ->export()
    }

}
