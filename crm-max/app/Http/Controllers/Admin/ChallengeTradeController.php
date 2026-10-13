<?php

namespace App\Http\Controllers\Admin;

use App\Events\TradeUpdated;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Trade;
use App\Services\TradeBalanceService;
use App\Services\TradeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ProtoneMedia\Splade\Facades\Toast;

/**
 * Editable trades view for a single Prop Firm challenge, mirroring what
 * Admin\TradeController already does for brokerage clients -- deliberately
 * a SEPARATE controller rather than extending TradeController, because that
 * one's update() writes profit adjustments straight to User::balance, which
 * is the wrong balance for a challenge (challenges track balance on their
 * own TradingAccount). Keeping this fully separate means there's no branch
 * to get wrong in the brokerage path, and no risk of a challenge trade edit
 * ever touching a client's brokerage wallet.
 */
class ChallengeTradeController extends Controller
{
    /**
     * Opens a trade directly on this challenge's own trading account.
     * Deliberately bypasses the shared /api/admin/trades + ResolveTradingAccount
     * flow: that middleware resolves "the" trading account off the
     * *authenticated admin* (via $request->user()), not the client being
     * traded for -- fine for a client with exactly one account, but it
     * silently saves a null trading_account_id (or rejects outright) for
     * any prop firm client, who always has 2+ accounts by construction
     * (their own + each challenge's). Since this route is already scoped
     * to one specific challenge, there's no ambiguity to resolve -- just
     * bind that challenge's own trading account directly and reuse
     * TradeService::openTrade()'s existing margin/balance/execution logic
     * as-is, so this stays behaviorally identical to a real trade.
     */
    public function store(Request $request, string $challengeId): JsonResponse
    {
        $challenge = $this->findChallenge($challengeId);

        if (! $challenge->tradingAccount) {
            return response()->json(['message' => 'This challenge has no trading account yet.'], 422);
        }

        $validated = $request->validate([
            'id' => ['required', 'exists:assets,id'],
            'type' => ['required', 'in:buy,sell'],
            'volume' => ['required', 'numeric', 'min:0.01'],
            'leverage' => ['required', 'numeric', 'min:1'],
            'margin' => ['nullable', 'numeric'],
            'is_take_profit' => ['nullable', 'boolean'],
            'take_profit' => ['nullable', 'numeric'],
            'is_stop_loss' => ['nullable', 'boolean'],
            'stop_loss' => ['nullable', 'numeric'],
        ]);

        $validated['user_id'] = $challenge->user_id;

        app()->instance('tradingAccount', $challenge->tradingAccount);
        $result = app(TradeService::class)->openTrade($validated);
        app()->forgetInstance('tradingAccount');

        if ($result['status'] < 1) {
            return response()->json(['message' => $result['msg']], 422);
        }

        broadcast(new TradeUpdated($challenge->user_id));
        log_activity('challenge_trade_opened', "Trade opened on challenge {$challenge->id} by ".auth()->user()->email.' at '.Carbon::now());

        return response()->json(['message' => $result['msg']]);
    }

    public function tradeData(string $challengeId): JsonResponse
    {
        $challenge = $this->findChallenge($challengeId);

        if (! $challenge->tradingAccount) {
            return response()->json(['data' => ['marginLevel' => format_price(0), 'marginLevelValue' => 0]]);
        }

        $available = app(TradeBalanceService::class)->availableToTrade($challenge->user, $challenge->tradingAccount);

        return response()->json(['data' => [
            'marginLevel' => format_price($available),
            'marginLevelValue' => $available,
        ]]);
    }

    public function edit(string $challengeId, string $tradeId)
    {
        $challenge = $this->findChallenge($challengeId);
        $trade = $this->findTrade($challenge, $tradeId);

        return view('admin.propfirm.challenges.edit_trade', [
            'challenge' => $challenge,
            'trade' => $trade,
            'default' => [
                'opened_at' => $this->dateTimeLocalValue($trade->getRawOriginal('opened_at')),
                'close_at' => $this->dateTimeLocalValue($trade->getRawOriginal('close_at')),
                'volume' => $trade->volume,
                'leverage' => $trade->leverage,
                'opening_price' => $trade->opening_price,
                'closing_price' => $trade->closing_price,
                'margin' => $trade->margin,
                'profit' => $trade->profit,
            ],
        ]);
    }

    public function update(Request $request, string $challengeId, string $tradeId)
    {
        $challenge = $this->findChallenge($challengeId);
        $trade = $this->findTrade($challenge, $tradeId);

        $validated = $this->getData($request);

        if ((float) $trade->profit !== (float) $validated['profit'] && $challenge->tradingAccount) {
            $difference = (float) $validated['profit'] - (float) $trade->profit;

            // Same method real trade settlement uses -- correctly threads
            // through the deposit/bonus/credit/profit priority waterfall if
            // wallet components are on for this company, plain balance
            // adjustment otherwise. Never touches users.balance.
            $challenge->tradingAccount->adjustBalanceForTrade(
                $difference,
                "Admin edit: trade #{$trade->trade_id} profit adjusted by ".auth()->user()->email
            );

            Toast::success('Challenge account balance updated.');
        }

        broadcast(new TradeUpdated($trade->user_id));

        $trade->update($validated);

        log_activity('challenge_trade_updated', "Trade #{$trade->trade_id} on challenge {$challenge->id} edited by ".auth()->user()->email.' at '.Carbon::now());

        Toast::success('Trade updated successfully');

        return redirect()->route('admin.propfirm.challenges.show', $challenge->id);
    }

    public function destroy(string $challengeId, string $tradeId)
    {
        $challenge = $this->findChallenge($challengeId);
        $trade = $this->findTrade($challenge, $tradeId);

        $trade->delete();

        log_activity('challenge_trade_deleted', "Trade #{$trade->trade_id} on challenge {$challenge->id} deleted by ".auth()->user()->email.' at '.Carbon::now());

        broadcast(new TradeUpdated($trade->user_id));
        Toast::title('Trade deleted successfully');

        return redirect()->route('admin.propfirm.challenges.show', $challenge->id);
    }

    public function getData(Request $request): array
    {
        return $request->validate([
            'margin' => 'required|numeric',
            'volume' => 'required|numeric',
            'leverage' => 'required|numeric',
            'profit' => 'required|numeric',
            'opening_price' => 'required|numeric',
            'closing_price' => 'required|numeric',
            'trade_type' => 'nullable',
            'opened_at' => 'sometimes|nullable|date',
            'close_at' => 'nullable',
        ]);
    }

    private function findChallenge(string $id): Challenge
    {
        return Challenge::withoutGlobalScope('company')
            ->where('id', $id)
            ->where('company_id', auth()->user()->company_id)
            ->with('tradingAccount')
            ->firstOrFail();
    }

    // Trade doesn't belong to a company directly, so scoping through the
    // challenge's own trading_account_id is what actually keeps an admin
    // from editing a trade that isn't on this challenge -- guessing/URL-
    // hacking a trade id from a different challenge or a different company
    // entirely gets a 404, not someone else's trade.
    private function findTrade(Challenge $challenge, string $tradeId): Trade
    {
        return Trade::query()
            ->where('id', $tradeId)
            ->where('trading_account_id', $challenge->trading_account_id)
            ->firstOrFail();
    }

    private function dateTimeLocalValue($value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i');
    }
}
