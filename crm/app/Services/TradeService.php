<?php

namespace App\Services;

use App\Events\TradeUpdated;
use App\Models\Asset;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use App\Support\MarketHours;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TradeService
{
    /**
     * The price updater runs every minute, so a couple of missed cycles is
     * the point at which "the market is quiet" stops being a plausible
     * explanation and the feed is the likelier suspect.
     */
    private const STALE_PRICE_WARNING_SECONDS = 300;

    public function __construct(
        protected ?TradeBalanceService $tradeBalanceService = null,
        protected ?TradeScopeService $tradeScopeService = null,
    ) {
        $this->tradeBalanceService ??= new TradeBalanceService;
        $this->tradeScopeService ??= new TradeScopeService;
    }

    public function openTrade($data)
    {

        $assetService = new AssetService;

        $coin = Asset::query()->where('id', $data['id'])->first();

        if (! $coin) {

            $data['status'] = 0;
            $data['msg'] = 'Sorry you cant submit this order';

            return $data;
        }

        $assetService->updateRate($coin);

        // Fallback only -- the client normally sends its own computed
        // 'margin', this just covers a client that doesn't. Must use the
        // resolved USD margin rate, not $coin->rate directly: for any
        // forex pair not quoted against USD (USDJPY, GBPJPY, EURGBP, ...)
        // raw rate is in the wrong currency entirely. See
        // ForexMarginRateResolver.
        $computedMargin = ((float) $data['volume'] * ForexMarginRateResolver::marginRate($coin)) / ($data['leverage'] ?? $coin->leverage);
        $margin = isset($data['margin']) && (float) $data['margin'] > 0
            ? (float) $data['margin']
            : $computedMargin;

        $data['margin'] = $margin;

        $volume = $data['volume'];

        $data['user_id'] = $data['user_id'] ?? auth()->id();

        $user = isset($data['user_id']) ? User::findOrFail($data['user_id']) : auth()->user();
        $tradingAccount = $this->tradeScopeService->currentTradingAccount($user);

        if (! $tradingAccount && TradingAccount::where('user_id', $user->id)->count() > 1) {
            $data['status'] = 0;
            $data['msg'] = 'Please select which trading account to place this order on.';

            return $data;
        }

        $is_manager = $data['user_id'] != auth()->id();

        //        Log::info('trade data',[
        //            'coin' => $coin,
        //            'rate' => $c_rate,
        //            'margin' => $margin,
        //            $user
        //        ]);

        if (! $user->can_trade) {
            $data['status'] = 0;
            $data['msg'] = 'Sorry you can not trade, contact support to enable trading';

            return $data;
        }

        if ($this->tradeBalanceService->availableToTrade($user, $tradingAccount) < $margin) {
            $data['status'] = 0;
            $data['msg'] = 'Insufficient balance, please fund your account';

            return $data;
        } else {
            $rate = $coin->rate;
            if (! $coin->is_active) {
                $data['status'] = 0;
                $data['msg'] = $coin->name.' is not currently active';

                return $data;
            } else {
                if ($rate > 0) {
                    //                    $data = $request->all();
                    // Enter at the price the customer actually gets -- the ask
                    // (buy_price) to buy, the bid (sell_price) to sell -- not
                    // the raw mid rate, so spread is a real cost for customers
                    // an admin has explicitly targeted via spread_multiplier.
                    // Everyone else still executes at the raw mid rate.
                    $data['open_rate'] = $data['type'] === 'sell'
                        ? $coin->executionSellPriceFor($user)
                        : $coin->executionBuyPriceFor($user);

                    //                    if($base != 'USD' && $amt_rate > 0){
                    //                        $data['base_rate'] = $amt_rate;
                    //                    }
                    $res = $this->initTrade($data, $user, $coin, $rate, $tradingAccount);

                    //
                    $msg = $user->email.' traded '.format_price($volume).' volume of '.$coin->name;
                    $message = 'Successfully opened for '.$coin->name;

                    $data['status'] = 1;
                    $data['msg'] = $message;
                    $data['data'] = $res;

                    $name = get_site_name();

                    $d_title = "Trade Successfully Opened - {$coin->name} Position.";

                    $d_msg = "An open trade of <strong>{$coin->sy }</strong> position has been successfully opened. The trade is now active, and we will continue to monitor the market closely to ensure optimal results.
<br/>
<br/>
Thank you for choosing $name.";

                    $notificationService = new NotificationService;

                    log_activity('new_trade', $msg);

                    if (company_settings('open_trade_email', settings('open_trade_email', 'no')) == 'yes') {
                        if (! $is_manager) {
                            $notificationService->sendEmailMessage($d_title, $d_msg, $user, queue: true);
                        }
                    }

                } else {
                    $data['status'] = 0;
                    $data['msg'] = 'You cannot trade '.$coin->name.' at the moment';

                    return $data;
                }
            }
        }

        return $data;
    }

    public function openTradeOld($data)
    {

        $assetService = new AssetService;

        $coin = Asset::select('id', 'name', 'rate', 'sym', 'base', 'type', 'is_active', 'ex_sym', 'leverage')->whereId($data['id'])->first();

        //if base is not USD, convert to USD
        $base = strtoupper($coin->base);

        //        if($base != 'USD'){
        //
        //            $data['amount'] = $amount / $coin->leverage;
        //
        //            $amt_rate = $assetService->getCurRate('USD', $coin->base, 'forex');
        //            if($amt_rate <= 0){
        //                $data['status'] = 0;
        //                $data['msg'] = 'Sorry you can not trade this currency pair';
        //                return $data;
        //            }
        //
        //            $data['amount'] = ($amt_rate * floatval($qty)) / $coin->leverage;
        //
        //        }

        $amt = $data['amount'];

        if ($amt <= 0) {
            $data['status'] = 0;
            $data['msg'] = 'Sorry you cant submit this order';

            return $data;
        }

        // Legacy path, no live callers -- routed through the same gate as
        // everything else so it can't reintroduce an unvalidated writer if it
        // is ever wired back up.
        $assetService->updateRate($coin);

        $user = isset($data['user_id']) ? User::findOrFail($data['user_id']) : auth()->user();
        if (! $user->can_trade) {
            $data['status'] = 0;
            $data['msg'] = 'Sorry you can not trade, contact support to enable trading';

            return $data;
        }
        if ($user->bal < $amt) {
            $data['status'] = 0;
            $data['msg'] = 'Insufficient balance, please fund your account';

            return $data;
        } else {
            $rate = $coin->rate;
            if (! $coin->is_active) {
                $data['status'] = 0;
                $data['msg'] = $coin->name.' is not currently active';

                return $data;
            } else {
                if ($rate > 0) {
                    //                    $data = $request->all();
                    $data['open_rate'] = $rate;

                    //                    if($base != 'USD' && $amt_rate > 0){
                    //                        $data['base_rate'] = $amt_rate;
                    //                    }
                    $res = $this->initTrade($data, $user, $coin, $rate);

                    //
                    $msg = $user->email.' traded '.format_price($amt).' worth of '.$coin->name;
                    $message = 'Successfully opened for '.$coin->name;

                    $data['status'] = 1;
                    $data['msg'] = $message;
                    $data['data'] = $res;
                    log_activity('new_trade', $msg);

                } else {
                    $data['status'] = 0;
                    $data['msg'] = 'You cannot trade '.$coin->name.' at the moment';

                    return $data;
                }
            }
        }

        return $data;
    }

    public function closeTrade($id, ?User $currentUser = null): array
    {
        try {
            //            if(settings('use_api') == 'no'){
            //                $dt['status'] = 0;
            //                $dt['msg'] = 'Trading disabled';
            //                return $dt;
            //            }
            $actor = $currentUser ?? auth()->user();
            $tradingAccount = $actor
                ? $this->tradeScopeService->currentTradingAccount($actor)
                : null;

            // Scope the lookup to the actor's trading account only for self-service
            // (new-platform) closes. Admin/manager/scheduler closes act on behalf of
            // other users and may run without an authenticated user, so they keep the
            // original unscoped lookup and existing v2 flows stay intact.
            $data = $tradingAccount
                ? $this->tradeScopeService->findTradeForUser($actor, $id, $tradingAccount)
                : Trade::findOrFail($id);

            $tradeUser = User::find($data->user_id);
            //            if($c_user->hasRole('user') && $c_user->id != $data->user_id){
            //                return response()->json(['message' => 'unauthorized'], 401);
            //            }

            // Recompute P&L fresh, synchronously, right before it's applied to
            // balance -- profit/closing_price are otherwise only refreshed by
            // the async UpdateTradeProfitJob (queued off getOpenTrades polls),
            // which can't keep up with a trade opened and closed again in quick
            // succession. Without this, closeTrade() applies whatever profit
            // value happened to be on the row -- still its just-opened default
            // for a fast round-trip -- crediting/debiting nothing and leaving
            // the back office (reads this column) disagreeing with the trade
            // room (computes its own live P&L off the tick stream). Only for
            // trades still actually open; don't rewrite a closed trade's
            // historical profit against today's price.
            if ($data->status === 0) {
                $this->updateTradeProfit($data);
            }

            $this->updateTrade($data, $tradeUser);

            $user = auth()->user();

            if ($data->asset && $data->user_id == $user?->id) {
                $coin = $data->asset;

                $d_title = "Trade Successfully Closed - {$coin->sy} Position.";

                $name = get_site_name();

                $d_msg = "Your <strong>{$coin->sy}</strong> trade has been successfully closed. The trade has been executed at the optimal point, securing the expected results.
<br/>
<br/>
Thank you for choosing $name";

                $notificationService = new NotificationService;

                if (company_settings('close_trade_email', settings('close_trade_email', 'no')) == 'yes') {
                    $notificationService->sendEmailMessage($d_title, $d_msg, $user, queue: true);
                }

            }

            broadcast(new TradeUpdated($data->user_id));

            if (! $data) {
                $dt['status'] = 0;
                $dt['msg'] = 'Trade not found';

                return $dt;
            }

            $authUser = auth()->user() ?? $tradeUser;
            $actorEmail = $authUser?->email ?? 'system';
            $msg = $data->currency_pair.' trade closed by '.$actorEmail.' at '.Carbon::now();
            log_activity('closed_trade', $msg);

            return [
                'status' => 1,
                'asset' => $data->asset,
                'msg' => 'Trade successfully closed',
            ];
        } catch (\Throwable $th) {
            return [
                'status' => 0,
                'msg' => $th->getMessage(),
                'message' => $th->getMessage(),
            ];
        }
    }

    public function updateTrade($trade, $user)
    {
        if (settings('use_api') == 'no') {
            return response()->json([]);
        }

        // The close claim and the settlement commit together: if settling throws, the claim is
        // undone and the trade stays open for the next check, rather than closed but unpaid.
        return DB::transaction(fn () => $this->settleTrade($trade, $user));
    }

    private function settleTrade($trade, $user)
    {
        //        $user = Auth::user();
        //        $trade = Trade::findOrFail($id);
        //        $user = User::findOrFail($trade->user_id);

        // Held as a Carbon, because reading it back off the model does not give
        // one: Trade::getCloseAtAttribute() formats close_at as 'd-m-Y H:i:s'
        // for display, and MySQL rejects that outright. The claim below binds
        // this variable rather than $trade->close_at for exactly that reason --
        // TradeObserver and AccountStatementService already reach past the same
        // accessor with getAttributes()/getRawOriginal().
        $closedAt = Carbon::now();
        $trade->close_at = $closedAt;
        $trade->loadMissing('tradingAccount');

        $pl = $trade->profit;

        if ($trade->status === 0) {
            // Claim the close atomically. The price watcher, the profit job and the client's own
            // close can each hold this trade open in memory; only the one that flips the row
            // settles it, so the P&L is never credited twice.
            $claimed = Trade::withoutGlobalScopes()->whereKey($trade->id)->where('status', 0)
                ->update(['status' => 1, 'close_at' => $closedAt]);
            if ($claimed === 0) {
                return null;
            }

            if ($trade->trade_type == 'buy') {
                if ($trade->opening_price > $trade->closing_price) {

                    $msg = 'Traded  '.optional($trade->currency)->name.' lost';
                    //                    $amt = $trade->traded_amount + ($pl);
                    $amt = $pl;
                    $this->tradeAddBalance($user, $amt, $msg, $trade->tradingAccount);

                    $trade->result = 2;
                    $trade->status = 1;
                    $trade->save();

                    return 2;
                } elseif ($trade->opening_price < $trade->closing_price) {
                    //                    $user->balance += ($trade->traded_amount + $pl);
                    $msg = 'Traded  '.optional($trade->currency)->name.' won';
                    //                    $amt = $trade->traded_amount + ($pl);
                    $amt = $pl;
                    $this->tradeAddBalance($user, $amt, $msg, $trade->tradingAccount);
                    $user->save();
                    $trade->result = 1;
                    $trade->status = 1;
                    $trade->save();

                    return 1;
                } else {
                    $msg = 'Traded  '.optional($trade->currency)->name.' draw';

                    $trade->result = 3;
                    $trade->status = 1;
                    $trade->save();

                    return 3;
                }
            } elseif ($trade->trade_type == 'sell') {
                if ($trade->opening_price < $trade->closing_price) {

                    $msg = 'Traded  '.optional($trade->asset)->name.' lost';
                    //                    $amt = $trade->traded_amount + ($pl);
                    $amt = $pl;
                    $this->tradeAddBalance($user, $amt, $msg, $trade->tradingAccount);

                    $trade->result = 2;
                    $trade->status = 1;
                    $trade->save();

                    return 2;
                } elseif ($trade->opening_price > $trade->closing_price) {
                    //                    $user->balance += ($trade->traded_amount + $pl);
                    //                    $user->save();
                    $msg = 'Traded  '.optional($trade->asset)->name.' won';
                    $amt = $pl;
                    $this->tradeAddBalance($user, $amt, $msg, $trade->tradingAccount);
                    $trade->result = 1;
                    $trade->status = 1;
                    $trade->save();

                    return 1;
                } else {
                    //                    $user->balance += $trade->traded_amount;
                    //                    $user->save();

                    $msg = 'Traded  '.optional($trade->asset)->name.' draw';
                    //                    $amt = $trade->traded_amount;
                    //                    $this->tradeAddBalance($user, $amt, $msg);

                    $trade->result = 3;
                    $trade->status = 1;
                    $trade->save();

                    return 3;
                }
            }
        }
    }

    public function closeAllUserTrades($user_id)
    {
        $trades = Trade::query()->where('user_id', $user_id)->whereStatus(0)->get();
        $user = User::findOrFail($user_id);
        if (count($trades) > 0) {
            foreach ($trades as $item) {
                $this->updateTrade($item, $user);
            }
        }
        if ($user->balance < 0) {
            $user->balance = 0;
            $user->save();
        }

        return response()->json($trades);
    }

    public function tradeAddBalance($user, $amount, $msg, ?TradingAccount $tradingAccount = null): bool
    {
        return $this->tradeBalanceService->applyTradePnl($user, (float) $amount, $tradingAccount, $msg);
        //        Transaction::create(['user_id' => $user->id, 'amount' => $amount, 'type' => 'credit', 'account_type' => 'balance','note' => $msg]);
    }

    /**
     * Change (or remove) the take-profit / stop-loss levels on the customer's
     * own open trade. Either level can be set without the other; null or 0
     * removes it. Levels are prices (see updateTradeProfit step 9), so they are
     * checked against the current executable price: a buy's TP must be above
     * it and its SL below, a sell the reverse. Otherwise the level would fire
     * on the next tick.
     */
    public function updateTakeProfitStopLoss(
        string $id,
        User $actor,
        ?float $takeProfit,
        ?float $stopLoss,
        bool $updateTakeProfit = true,
        bool $updateStopLoss = true
    ): array {
        $trade = $this->tradeScopeService->findTradeForUser(
            $actor,
            $id,
            $this->tradeScopeService->currentTradingAccount($actor)
        );

        if ($trade->status !== 0) {
            return ['status' => 0, 'msg' => 'This trade is already closed.'];
        }

        $takeProfit = $updateTakeProfit && $takeProfit > 0 ? $takeProfit : null;
        $stopLoss = $updateStopLoss && $stopLoss > 0 ? $stopLoss : null;

        // Refresh closing_price to the live executable price first. That can
        // itself close the trade when the existing TP/SL has just been hit.
        $this->updateTradeProfit($trade);
        $trade->refresh();

        if ($trade->status !== 0) {
            return ['status' => 0, 'msg' => 'This trade has just closed.'];
        }

        $price = (float) $trade->closing_price;
        $isBuy = $trade->trade_type === 'buy';

        if ($updateTakeProfit && $price > 0 && $takeProfit !== null && ($isBuy ? $takeProfit <= $price : $takeProfit >= $price)) {
            return ['status' => 0, 'msg' => 'Take profit must be '.($isBuy ? 'above' : 'below').' the current price ('.$price.').'];
        }

        if ($updateStopLoss && $price > 0 && $stopLoss !== null && ($isBuy ? $stopLoss >= $price : $stopLoss <= $price)) {
            return ['status' => 0, 'msg' => 'Stop loss must be '.($isBuy ? 'below' : 'above').' the current price ('.$price.').'];
        }

        if ($updateTakeProfit) {
            $trade->is_take_profit = $takeProfit !== null;
            $trade->take_profit = $takeProfit ?? 0;
        }

        if ($updateStopLoss) {
            $trade->is_stop_loss = $stopLoss !== null;
            $trade->stop_loss = $stopLoss ?? 0;
        }
        $trade->save();

        return ['status' => 1, 'msg' => 'Take profit / stop loss updated.', 'trade' => $trade];
    }

    public function updateTradeProfitOld($trade)
    {
        $assetService = new AssetService;
        if (! $trade->asset) {
            return $trade->delete();
        }
        $user = $trade->load('user');
        $rate = optional($trade->asset)->rate;

        $coinPrice = $assetService->getRate($trade->asset->coin_id);
        if ($coinPrice == 0) {
            $coinPrice = $rate;
        }
        $coinPrice = (float) $coinPrice;
        //        $old_coin_value = $trade->amount / $trade->opening_price;
        $old_coin_value = $trade->opening_price;
        $trade->closing_price = round($coinPrice, 6);
        $cryptoRate = $coinPrice;
        $trade->close_at = null;
        $pl = abs(($old_coin_value * $cryptoRate) - $trade->volume);

        $pl = round($pl, 6);

        $trade->profit = $pl;

        if ($trade->trade_type == 'buy') {
            if ($trade->opening_price > $cryptoRate) {
                $trade->profit = $pl * (-1);
                $trade->save();
            } elseif ($trade->opening_price < $cryptoRate) {
                $trade->profit = $pl;
                $trade->save();
            }
        } elseif ($trade->trade_type == 'sell') {
            if ($trade->opening_price < $cryptoRate) {
                $trade->profit = $pl * (-1);
                $trade->save();
            } elseif ($trade->opening_price > $cryptoRate) {
                $trade->profit = $pl;
                $trade->save();
            }
        }

        //        return $trade;

        //check profit / loss
        if ($trade->take_profit > 0) {
            if ($trade->profit >= $trade->take_profit) {
                $this->updateTrade($trade, $user);
            }
        }
        if ($trade->stop_loss > 0) {
            if (((-1) * $trade->stop_loss) >= $trade->profit) {
                $this->updateTrade($trade, $user);
            }
        }

        return $trade;
    }

    public function updateTradeProfit($trade)
    {
        if (! $trade->relationLoaded('asset')) {
            $trade->load('asset');
        }

        if (! $trade->asset) {
            log_activity('trade_auto_deleted', 'Trade with ID '.$trade->trade_id.' deleted due to missing asset.');
            $trade->delete();

            return null;
        }

        $trade->load('user');

        $assetService = new AssetService;

        //        $currentPrice = (float) $assetService->getRate($trade->asset->coin_id);
        //        if ($currentPrice <= 0) {
        //            $currentPrice = (float) optional($trade->asset)->rate;
        //        }

        // Closing a buy means selling (you get the bid); closing a sell means
        // buying back (you pay the ask). Using the opposite side here -- not
        // the raw mid rate -- is what makes a freshly opened trade show a
        // small immediate float loss equal to the spread, same as opening_price
        // now being set from buy/sell price in openTrade() above. Gated the
        // same way: only real cost for a customer an admin explicitly
        // targeted via spread_multiplier, raw mid rate for everyone else.
        $currentPrice = (float) ($trade->trade_type === 'sell'
            ? $trade->asset->executionBuyPriceFor($trade->user)
            : $trade->asset->executionSellPriceFor($trade->user));

        // PnL is only as current as the price behind it. Asset.rate carries no
        // guarantee of freshness on its own, so say so out loud when a
        // position is being marked against a price the feed stopped
        // refreshing -- silently reporting a stale number as live PnL is what
        // makes the back office and the trade room look like they disagree.
        $priceAge = $trade->asset->price_age_seconds;
        if ($priceAge !== null && $priceAge > self::STALE_PRICE_WARNING_SECONDS) {
            Log::channel('price')->warning('Open-trade PnL computed from a stale price', [
                'trade_id' => $trade->trade_id ?? $trade->id,
                'asset' => $trade->asset->sym_key ?? $trade->asset->sym,
                'price_age_seconds' => $priceAge,
                'price_source' => $trade->asset->price_source,
            ]);
        }

        $volume = (float) $trade->volume;

        $openingPrice = (float) $trade->opening_price;

        // 7. Compute P/L based on trade type
        if ($trade->trade_type === 'buy') {
            $profit = ($currentPrice - $openingPrice) * $volume;
        } elseif ($trade->trade_type === 'sell') {
            $profit = ($openingPrice - $currentPrice) * $volume;
        } else {
            $profit = 0;
        }

        // (price difference x volume) is denominated in the instrument's QUOTE
        // currency, not the account currency -- a EURJPY position's P&L is in
        // yen, an AU200/AUD position's is in Australian dollars. This used to
        // be stored and displayed as USD regardless, so every instrument not
        // quoted against USD reported P&L inflated by that currency's own
        // exchange rate (~159x on JPY crosses). Invisible on EURUSD/XAUUSD
        // and every other USD-quoted instrument, where the factor is exactly
        // 1 -- which is why it looked intermittent.
        $profit *= ForexMarginRateResolver::quoteToUsd($trade->asset);

        // 8. Round and persist
        $profit = round($profit, 6);
        $trade->closing_price = round($currentPrice, 6);

        $trade->profit = $profit;
        //
        //        Log::info("Trade: ",[
        //            $profit,
        //            $volume,
        //            $currentPrice,
        //            $trade->opening_price,
        //            $trade->asset->coin_id,
        //        ]);

        $trade->save();

        // 9. Check take-profit / stop-loss. TP and SL are price levels the
        // customer set on the instrument (confirmed against real trade
        // history -- a BTC trade entered at 64,795 had TP stored as 63,500,
        // a EUR/USD trade entered at 1.14471 had TP stored as 1.15), so they
        // have to be compared against the current market price, not against
        // the dollar profit figure. Comparing profit to a price level meant
        // TP/SL effectively never fired on expensive instruments (profit
        // rarely reaches a number as large as a BTC or gold price) and fired
        // almost immediately on cheap ones (profit clears a number like 1.15
        // within a dollar or two of opening), regardless of where price
        // actually was. Direction matters: a buy profits as price rises, so
        // its TP is above entry and its SL is below; a sell is the reverse.
        $takeProfitHit = $trade->take_profit > 0 && (
            $trade->trade_type === 'buy'
                ? $currentPrice >= $trade->take_profit
                : $currentPrice <= $trade->take_profit
        );
        $stopLossHit = $trade->stop_loss > 0 && (
            $trade->trade_type === 'buy'
                ? $currentPrice <= $trade->stop_loss
                : $currentPrice >= $trade->stop_loss
        );
        // While the market is closed its price is the last tick, so TP/SL wait for the first
        // open price (MarketHours). Stop-outs (MarginCall) are separate and keep running.
        if (($takeProfitHit || $stopLossHit) && MarketHours::status($trade->asset)['open']) {
            // Fill rule (user's decision, 12 Oct): a take profit fills at exactly its level, even
            // when the market gapped past it; a stop loss fills at the price that reached it.
            // Saved by updateTrade() only if this closer wins the claim.
            if ($takeProfitHit && ! $stopLossHit) {
                $level = (float) $trade->take_profit;
                $trade->closing_price = round($level, 6);
                $trade->profit = round(($trade->trade_type === 'buy' ? $level - $openingPrice : $openingPrice - $level)
                    * $volume * ForexMarginRateResolver::quoteToUsd($trade->asset), 6);
            }

            // Recorded only by the closer that actually settled it (see updateTrade's claim).
            if (in_array($this->updateTrade($trade, $trade->user), [1, 2, 3], true)) {
                $trade->forceFill(['close_reason' => $stopLossHit ? 'stop_loss' : 'take_profit'])->save();
            }
        }

        return $trade;
    }

    public function applyPnlToOpenTrade(Trade $trade, float $pnl, ?float $currentRate = null): Trade
    {
        if (! $trade->relationLoaded('asset')) {
            $trade->load('asset');
        }

        if ($currentRate === null || $currentRate <= 0) {
            $trade->loadMissing('user');

            // Use the same executable side as updateTradeProfit(). In
            // particular, a sell closes at the customer's ask price, which
            // may include their spread. Back-solving from the raw midpoint
            // would therefore jump as soon as the next live P&L refresh ran.
            $currentRate = $trade->asset
                ? (float) ($trade->trade_type === 'sell'
                    ? $trade->asset->executionBuyPriceFor($trade->user)
                    : $trade->asset->executionSellPriceFor($trade->user))
                : (float) $trade->closing_price;
        }

        $trade->opening_price = $this->getOpeningRateFromPnl(
            $pnl,
            $currentRate,
            (float) $trade->volume,
            $trade->trade_type,
            $trade->asset ? ForexMarginRateResolver::quoteToUsd($trade->asset) : 1.0
        );
        $trade->closing_price = round($currentRate, 6);
        $trade->profit = round($pnl, 6);
        $trade->save();

        return $trade;
    }

    /**
     * Back-solve the opening price that makes an open trade show $pnl.
     *
     * This MUST stay the exact inverse of the P&L derivation in
     * updateTradeProfit(), which is:
     *
     *     profit = (current - open) * volume * quoteToUsd
     *
     * so the inverse has to divide by $quoteRate as well as volume. Without
     * it, a target P&L set on any instrument not quoted against USD lands
     * out by that currency's own exchange rate -- ~159x on JPY crosses.
     * Defaults to 1.0, which is exactly right for every USD-quoted
     * instrument and preserves the previous behaviour for them.
     */
    public function getOpeningRateFromPnl(
        float $pnl,
        float $currentRate,
        float $volume,
        string $tradeType,
        float $quoteRate = 1.0
    ): float {
        if ($volume <= 0) {
            throw new \InvalidArgumentException('Volume must be greater than zero.');
        }

        if ($quoteRate <= 0) {
            $quoteRate = 1.0;
        }

        $priceDelta = $pnl / ($volume * $quoteRate);

        switch ($tradeType) {
            case 'buy':
                $openRate = $currentRate - $priceDelta;
                break;

            case 'sell':
                $openRate = $currentRate + $priceDelta;
                break;

            default:
                throw new \InvalidArgumentException("tradeType must be 'buy' or 'sell'.");
        }

        return round($openRate, 6);
    }

    public function getOpeningRateFromPnlOld2(float $pnl, float $currentRate, float $amount, string $tradeType): float
    {

        $isPnlPositive = $pnl >= 0;

        $absPnl = abs($pnl);

        if (($tradeType === 'buy' && $isPnlPositive) || ($tradeType === 'sell' && ! $isPnlPositive)) {
            return $amount * $currentRate / ($amount + $absPnl);
        } else {
            return $amount * $currentRate / ($amount - $absPnl);
        }
    }

    public function getOpeningRateFromPnlD(float $pnl, float $currentRate, float $amount, string $tradeType): float
    {
        $isPnlPositive = $pnl >= 0;
        $absPnl = abs($pnl);

        if (($tradeType === 'buy' && $isPnlPositive) || ($tradeType === 'sell' && ! $isPnlPositive)) {
            return $amount * $currentRate / ($amount + $absPnl);
        } else {
            // Ensure denominator is positive (at least a small percentage of amount)
            $safeDenominator = max($amount - $absPnl, $amount * 0.01);

            return $amount * $currentRate / $safeDenominator;
        }
    }

    public function initTrade($data, $user, $coin, $rate, ?TradingAccount $tradingAccount = null): Trade
    {
        $trade = new Trade;
        $user_id = $user->id;
        if (auth()->id() != $user_id) {
            $trade->by_admin = true;
        }
        if (isset($data['base_rate'])) {
            $trade->base_rate = $data['base_rate'];
        }
        $trade->user_id = $user_id;
        $trade->trading_account_id = $tradingAccount?->id;
        $trade->asset_id = $coin->id;
        $trade->trade_id = generateUniqueTradeId();
        $trade->trade_type = $data['type'];
        $trade->traded_amount = $data['margin'] ?? $data['amount'];
        $trade->volume = $data['volume'] ?? 0;
        $trade->lot = $data['lot'] ?? 1;
        $trade->margin = $data['margin'] ?? $data['amount'];
        $trade->is_take_profit = $data['is_take_profit'];
        $trade->take_profit = $data['take_profit'] ?? 0;
        $trade->is_stop_loss = $data['is_stop_loss'];
        $trade->stop_loss = $data['stop_loss'] ?? 0;
        //        $trade->currency_pair = $coin->ex_sym;
        $trade->leverage = $data['leverage'] ?? $coin->leverage;
        //        $trade->duration = $data['duration'];
        //        $trade->close_at = null;
        $trade->opening_price = $data['open_rate'];
        $trade->closing_price = $rate;
        //        $trade->overnight = Carbon::createFromFormat('Y-m-d H:i:s',now())->format('Y-m-d');;
        $trade->save();

        return $trade;
        //        return $trade;
    }
}
