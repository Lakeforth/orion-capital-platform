<?php

namespace App\Services;

use App\Events\TradeUpdated;
use App\Models\Company;
use App\Models\Trade;

/**
 * Acts on a price as soon as it is stored: every time the live stream or the poll writes new
 * prices, the open trades with a TP or SL and the pending orders on exactly those instruments
 * are checked, whether or not their client is online. Closing and opening go through the same
 * paths as everywhere else (TradeService::updateTradeProfit, PendingOrderService), so margin,
 * balance, the market-hours rule and the once-only settlement claim all apply unchanged.
 */
class PriceWatch
{
    /** @param  array<int, string>  $assetIds  instruments whose price was just written */
    public function check(array $assetIds): void
    {
        if ($assetIds === []) {
            return;
        }

        $previous = app()->bound('company') ? app('company') : null;

        try {
            Trade::withoutGlobalScopes()
                ->where('status', 0)
                ->whereIn('asset_id', $assetIds)
                ->where(fn ($query) => $query->where('take_profit', '>', 0)->orWhere('stop_loss', '>', 0))
                ->with(['asset', 'user', 'tradingAccount', 'currency'])
                ->get()
                ->each(function (Trade $trade) {
                    // Spreads and wallet settlement read the trade's own tenant settings, as in app:margin_call.
                    $company = Company::findCached($trade->company_id);
                    $company ? app()->instance('company', $company) : app()->forgetInstance('company');

                    rescue(function () use ($trade) {
                        (new TradeService)->updateTradeProfit($trade);

                        if ((int) $trade->fresh()?->status === 1) {
                            broadcast(new TradeUpdated($trade->user_id));
                        }
                    });
                });
        } finally {
            $previous ? app()->instance('company', $previous) : app()->forgetInstance('company');
        }

        rescue(fn () => app(PendingOrderService::class)->checkAssets($assetIds));
    }
}
