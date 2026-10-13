<?php

namespace App\Tables;

use App\Models\Trade;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class OrderBook extends AbstractTable
{

    public string $user_id;
    public string $status;
    public function __construct($user_id, $status)
    {
        $this->user_id = $user_id;
        $this->status = $status;
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
        $trades = Trade::query();
        if($this->user_id){
            $trades->where('user_id', $this->user_id);
        }
        if($this->status){
            $trades->where('status', $this->status);
        }
        return $trades;
    }


    public function configure(SpladeTable $table)
    {
        $table
            ->column('open_at', canBeHidden: false, sortable: true)
            ->column('qty', canBeHidden: false, sortable: true)
            ->column('opening_price', canBeHidden: false, sortable: true)
            ->column('closing_price', canBeHidden: false, sortable: true)
            ->column('profit', canBeHidden: false, sortable: true)
            ->column('take_profit', canBeHidden: false, sortable: true)
            ->column('stop_loss', canBeHidden: false, sortable: true)
            ->paginate(10);

            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
