<?php

namespace App\Jobs;

use App\Models\Trade;
use App\Services\ForexMarginRateResolver;
use App\Services\PendingOrderService;
use App\Services\TradeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateTradeProfitJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $user_id)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // P&L conversion to the account currency reads a statically cached
        // FX snapshot. A queue worker is a long-lived process, so without
        // this it would mark every position for the rest of the worker's
        // life against whatever rates happened to be loaded first.
        ForexMarginRateResolver::flush();

        $tradeService = new TradeService();

        $id = $this->user_id;
//
//        Log::info('Updating trade profit for user: ' . $id);

        // Open any of this client's pending orders the market has reached, before the
        // open trades are re-priced below so a just-opened one is included.
        app(PendingOrderService::class)->checkUser($id);

        $open_trades = Trade::whereUserId($id)->where('status', 0)->latest()->get();

        foreach ($open_trades as $trade) {
            $tradeService->updateTradeProfit($trade);
        }

    }
}
