<?php

namespace App\Tables;

use App\Models\Trade;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\AbstractTable;
use ProtoneMedia\Splade\SpladeTable;

/**
 * Trades for one specific challenge's trading account -- deliberately
 * scoped by trading_account_id, unlike TradeTable (brokerage), which scopes
 * by user_id and would pull in a client's other accounts/other challenges
 * too. A client can have multiple challenges over time, each with its own
 * trading account, so user_id alone is never the right scope here.
 */
class ChallengeTradeTable extends AbstractTable
{
    public function __construct(public ?string $tradingAccountId = null, public ?string $type = null)
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

        if ($this->tradingAccountId) {
            $query->where('trading_account_id', $this->tradingAccountId);
        } else {
            // No account yet (challenge not provisioned) -- show nothing
            // rather than accidentally falling through to every trade.
            $query->whereRaw('1 = 0');
        }

        if ($this->type) {
            $status = $this->type === 'open' ? 0 : 1;
            $query->where('status', $status);
        }

        return $query;
    }

    public function configure(SpladeTable $table)
    {
        $table
            ->withGlobalSearch(columns: ['trade_id', 'volume', 'trade_type'])
            ->defaultSort('created_at', 'desc')
            ->column('status', label: 'Status', sortable: true)
            ->column('trade_id', label: 'ID', sortable: true)
            ->column('opened_at', label: 'Open Time', sortable: true)
            ->column('close_at', label: 'Close Time', sortable: true)
            ->column('asset.sy', 'Instrument', sortable: false)
            ->column('volume', 'Volume', canBeHidden: false, sortable: true)
            ->column('trade_type', 'Direction', sortable: true)
            ->column('leverage', sortable: true)
            ->column('margin', sortable: true)
            ->column('opening_price', 'Open Rate', canBeHidden: false, sortable: true);

        if ($this->type === 'closed') {
            $table->column('closing_price', 'Closed Rate', canBeHidden: false, sortable: true);
        } else {
            $table->column('closing_price', 'Market Rate', canBeHidden: false, sortable: true);
        }

        $table
            ->column('profit', sortable: true)
            ->column('take_profit', sortable: true)
            ->column('stop_loss', sortable: true)
            ->column('actions')
            ->paginate(10);
    }
}
