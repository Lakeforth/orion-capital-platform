<?php

namespace App\Http\Controllers;

use App\Events\TradeUpdated;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use App\Services\AssetService;
use App\Services\ForexMarginRateResolver;
use App\Services\MarginCalculator;
use App\Services\TradeBalanceService;
use App\Services\TradeScopeService;
use App\Services\TradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ProtoneMedia\Splade\Facades\Toast;

class TradeController extends Controller
{
    public function store(Request $request)
    {
        if ($this->customerHasAiTradeEnabled()) {
            return $this->errorResponse($this->aiTradeDisabledMessage(), 403);
        }

        $validated = $this->getTradeData($request);

        //        $validated['type'] = $validated['direction'];
        //        $validated['id'] = $validated['selected_asset'];
        //        $validated['amount'] = $validated['required_margin'];

        $tradeService = new TradeService;

        Toast::success('Trade submited');

        $validated['amount'] = $validated['margin'] ?? 0;

        $data = $tradeService->openTrade($validated);

        if ($data['status'] < 1) {
            return $this->errorResponse($data['msg']);
        } else {

            return $this->successResponse($data['msg']);

        }

        //        Toast::success('Trade submited');

    }

    public function adminStore(Request $request)
    {

        $validated = $this->getTradeData($request);

        $tradeService = new TradeService;

        Toast::success('Trade submited');

        $user_id = $validated['user_id'] ?? auth()->id();

        $validated['amount'] = $validated['margin'] ?? 0;

        $data = $tradeService->openTrade($validated);

        //        Log::info("updated $user_id", [$data, $validated]);
        broadcast(new TradeUpdated($user_id));

        if ($data['status'] < 1) {
            return $this->errorResponse($data['msg']);
        } else {
            return $this->successResponse($data['msg']);
        }

        //        Toast::success('Trade submited');

    }

    public function getTradeData(Request $request)
    {
        return $request->validate([
            'volume' => 'required|min:1',
            'is_take_profit' => 'nullable',
            'take_profit' => 'nullable',
            'is_stop_loss' => 'nullable',
            'stop_loss' => 'nullable',
            'user_id' => 'nullable',
            'direction' => 'nullable',
            'type' => 'required',
            'leverage' => 'required',
            'id' => 'required',
            'margin' => 'required',
        ]);
    }

    public function tradeData(MarginCalculator $marginCalculator)
    {

        $user = User::findOrFail(auth()->id());
        $tradeScopeService = new TradeScopeService;
        $tradeBalanceService = new TradeBalanceService;
        $tradingAccount = $tradeScopeService->currentTradingAccount($user);

        $user_balance = $tradeBalanceService->balance($user, $tradingAccount);

        //        if(has_accounts()){
        //            $user_balance = format_price(auth()->user()->bonus_wallet()->balance + auth()->user()->credit_wallet()->balance + $user?->balance);
        //        }

        $user_balance = (float) $user_balance;
        $user_profit = $tradeBalanceService->pnl($user, $tradingAccount);

        $equity = $tradeBalanceService->equity($user, $tradingAccount);

        $margin = $tradeBalanceService->margin($user, $tradingAccount);

        $free_margin = $marginCalculator->calculateFreeMargin($user_balance, $user_profit, $margin);

        $marginL = $marginCalculator->calculateMarginLevel($user_balance, $user_profit, $margin);

        $data = [
            'margin' => format_price($margin),
            'marginValue' => $margin,
            'balance' => format_price($user_balance),
            'pnl' => number_format($user_profit, 3),
            'freeMargin' => format_price($free_margin),
            'marginLevel' => $marginL > 0 ? format_number($marginL).'%' : '∞',
            'marginLevelValue' => $marginL > 0 ? format_number($marginL).'%' : '∞',
            'has_account' => has_accounts(),
            'ai_trade' => (bool) $user->ai_trade,
            'ai_trade_module_enabled' => company_settings('ai_trade', 'no') === 'yes',
            'ai_trade_disabled_message' => $this->aiTradeDisabledMessage(),
        ];

        if ($tradingAccount) {
            $data['trading_account_id'] = $tradingAccount->account_id;
            $data['bonus'] = format_price($tradingAccount->bonus);
            $data['equity'] = format_price($equity);
        }

        if (! $tradingAccount && has_accounts()) {
            $data['credit_wallet'] = format_price(auth()->user()->credit_wallet()->balance);
            $data['bonus_wallet'] = format_price(auth()->user()->bonus_wallet()->balance);
            $data['total_balance'] = format_price(auth()->user()->bonus_wallet()->balance + auth()->user()->credit_wallet()->balance + $user?->balance);
        }

        $data['limits'] = plan_open_trade_context($user, $tradingAccount);

        return $this->successResponse('trade data', $data);
    }

    protected function customerHasAiTradeEnabled(): bool
    {
        $user = auth()->user();

        return $user instanceof User && (bool) $user->ai_trade;
    }

    protected function aiTradeDisabledMessage(): string
    {
        return (string) company_settings('ai_trade_disabled_message', 'please disable AI Trading');
    }

    public function adminTradeData($id, Request $request)
    {
        $user = User::findOrFail($id);

        // An explicit ?account= (the MT5-style account number, same value
        // ResolveTradingAccount matches on) lets the admin UI's account
        // picker show the right figures for a specific account when the
        // client has more than one -- otherwise fall back to auto-resolving
        // only when unambiguous (same rule ResolveTradingAccount itself
        // uses), same as before.
        $accountId = $request->query('account');

        $tradingAccount = $accountId
            ? TradingAccount::where('user_id', $user->id)->where('account_id', $accountId)->first()
            : null;

        if (! $tradingAccount && ! $accountId) {
            $accounts = TradingAccount::where('user_id', $user->id)->get();
            $tradingAccount = $accounts->count() === 1 ? $accounts->first() : null;
        }

        $tradeBalanceService = new TradeBalanceService;
        $balance = $tradeBalanceService->balance($user, $tradingAccount);
        $margin = $tradeBalanceService->margin($user, $tradingAccount);
        $available = $tradeBalanceService->availableToTrade($user, $tradingAccount);

        $data = [
            'margin' => format_price($margin),
            'balance' => format_price($balance),
            'pnl' => $tradeBalanceService->pnl($user, $tradingAccount),
            'freeMargin' => format_price($available - $margin),
            'marginLevel' => format_price($available),
            'marginLevelValue' => $available,
        ];

        return $this->successResponse('trade data', $data);
    }

    public function close($id)
    {
        $tradeService = new TradeService;

        $data = $tradeService->closeTrade($id, auth()->user());

        if ($data['status'] < 1) {
            return $this->errorResponse($data['msg']);
        } else {
            return $this->successResponse($data['msg']);
        }
    }

    public function update(Request $request, $id)
    {

        $trade = Trade::findOrFail($id);

        $trade->opening_price = $request['opening_price'];

        $data = [];

        if ($request->has('profit')) {
            $profit = $request['profit'];

            $tradeService = new TradeService;

            $assetService = new AssetService;

            // Back-solving an opening price from a target PnL has to use the
            // same validated price everything else does -- reading the raw
            // provider value here meant an outlier tick could be baked
            // permanently into a trade's opening price.
            $coinPrice = $assetService->currentRate($trade->asset);

            //            Log::info('coinPrice',['coinPrice'=>$coinPrice,'trade_id'=>$trade->id]);

            $trade->opening_price = $tradeService
                ->getOpeningRateFromPnl(
                    $profit,
                    $coinPrice,
                    $trade->volume,
                    $trade->trade_type,
                    $trade->asset ? ForexMarginRateResolver::quoteToUsd($trade->asset) : 1.0
                );

            Log::info('AssetApi key: ', [
                'profit' => $profit,
                'coinPrice' => $coinPrice,
                'volume' => $trade->volume,
                'trade_type' => $trade->trade_type,
            ]);

            if ($profit != 0) {
                $data['open_price'] = $trade->opening_price;
                $data['profit'] = $profit;
                $data['open_rate'] = $coinPrice;
            }

        }

        if ($request->has('opened_at')) {
            $trade->opened_at = $request['opened_at'] ?? $trade->opened_at;
        }

        $trade->save();

        broadcast(new TradeUpdated($trade->user_id));

        return $this->successResponse('updated', $data);

    }
}
