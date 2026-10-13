<?php

namespace App\Tables;

use App\Models\Trade;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

class TradeTable extends AbstractTable
{

    public function __construct(public ?string $id = null, public ?string $type = null, public ?string $tradingAccountId = null)
    {
        //
    }

    public function authorize(Request $request)
    {
        return true;
    }

    public function for()
    {
        $query = Trade::query();
        if($this->id){
            $query->where('user_id', $this->id);
        }
        if($this->tradingAccountId){
            $query->where('trading_account_id', $this->tradingAccountId);
        }
        if($this->type){
            $status = $this->type == 'open' ? 0 : 1;
            $query->where('status', $status);
        }

        return $query;
    }

    public function configure(SpladeTable $table)
    {
        // Closed trades should surface the most recently *closed* one first
        // -- created_at reflects when the trade was opened, which for a
        // trade that sat open a while before closing today would bury it
        // deep in the list even though it's the freshest thing to review.
        $sortColumn = $this->type === 'closed' ? 'close_at' : 'created_at';

        $table =
            $table
                ->withGlobalSearch(columns: ['trade_id','volume','trade_type'])
                ->defaultSort($sortColumn, 'desc')
                ->column('trade_id', label: 'ID', sortable: true)
                ->column('opened_at', label: 'Open Time', sortable: true)
                ->column('close_at', label: 'Close Time',  sortable: true)
                ->column('asset.sy', 'Instrument', sortable: false)
            ->column('volume','Volume', canBeHidden: false, sortable: true)
            ->column('trade_type','Direction', sortable: true)
            ->column('leverage',  sortable: true)
            ->column('margin',  sortable: true)
            ->column('opening_price','Open Rate', canBeHidden: false, sortable: true);
//            ->column('close_at', sortable: true)


        if ($this->type == 'closed'){
            $table->column('closing_price','Closed Rate', canBeHidden: false, sortable: true);
        }else{
            $table->column('closing_price','Market Rate', canBeHidden: false, sortable: true);
        }




        $table->column('profit',  sortable: true)
            ->column('take_profit', sortable: true)
            ->column('stop_loss',  sortable: true)
            ->column('actions')
            ->paginate(10);
            // ->searchInput()
            // ->selectFilter()
            // ->withGlobalSearch()

            // ->bulkAction()
            // ->export()
    }
}
