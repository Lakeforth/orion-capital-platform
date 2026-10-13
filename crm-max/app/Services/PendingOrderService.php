<?php

namespace App\Services;

use App\Events\TradeUpdated;
use App\Exceptions\PlanLimitExceededException;
use App\Models\Asset;
use App\Models\PendingOrder;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use App\Support\MarketHours;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Web trader pending (limit/stop) orders: placing, cancelling, editing TP/SL, and triggering.
 *
 * An order is a stored /trades/store body plus a price level. Nothing is reserved while it
 * waits. When the market crosses the level the body is replayed through TradeService::openTrade()
 * exactly as a market order would be, so margin, balance and plan checks apply at that moment.
 */
class PendingOrderService
{
    /** A quote older than this is re-read from the provider before deciding whether an order triggers. */
    public const PRICE_REFRESH_SECONDS = 5;

    /** How long triggered and cancelled orders stay in the ?status=all list. */
    public const HISTORY_DAYS = 7;

    /** The parts of a /trades/store body that are replayed when the order triggers. */
    private const PAYLOAD_KEYS = ['id', 'type', 'qty', 'volume', 'lot', 'amount', 'leverage', 'direction',
        'take_profit', 'stop_loss', 'is_take_profit', 'is_stop_loss'];

    public function place(User $user, ?TradingAccount $account, array $order, float $pendingPrice): PendingOrder
    {
        $side = $order['type'] ?? null;
        if (! in_array($side, ['buy', 'sell'], true)) {
            $this->fail('type', 'Choose buy or sell.');
        }

        $asset = Asset::query()->find($order['id'] ?? null);
        if (! $asset) {
            $this->fail('id', 'Sorry you cant submit this order');
        }
        if (! $asset->is_active) {
            $this->fail('id', $asset->name.' is not currently active');
        }

        $volume = (float) ($order['volume'] ?? 0);
        if ($volume <= 0) {
            $this->fail('volume', 'Volume must be greater than zero.');
        }

        if (! $account && TradingAccount::query()->where('user_id', $user->id)->count() > 1) {
            $this->fail('trading_account_id', 'Please select which trading account to place this order on.');
        }

        (new AssetService)->updateRate($asset);
        $market = $this->marketPrice($asset, $user, $side);
        if ($market <= 0) {
            $this->fail('pending_price', 'You cannot trade '.$asset->name.' at the moment');
        }
        if (abs($pendingPrice - $market) < 1e-9) {
            $this->fail('pending_price', 'Pending price must differ from the current price.');
        }

        $takeProfit = (float) ($order['take_profit'] ?? 0);
        $stopLoss = (float) ($order['stop_loss'] ?? 0);
        $this->assertLevels($side, $pendingPrice, $takeProfit, $stopLoss);

        $leverage = (int) ($order['leverage'] ?? 0) ?: (int) $asset->leverage;

        return PendingOrder::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'trading_account_id' => $account?->id,
            'asset_id' => $asset->id,
            'trade_id' => generateUniqueTradeId(),
            'trade_type' => $side,
            'order_type' => $this->orderType($side, $pendingPrice, $market),
            'pending_price' => $pendingPrice,
            'volume' => $volume,
            'leverage' => $leverage,
            'lot' => (int) ($order['lot'] ?? 1),
            'margin' => round($this->estimatedMargin($asset, $volume, $leverage, $pendingPrice), 2),
            'take_profit' => $takeProfit,
            'stop_loss' => $stopLoss,
            'payload' => Arr::only($order, self::PAYLOAD_KEYS),
            'status' => PendingOrder::STATUS_PENDING,
        ]);
    }

    /** This account's waiting orders, newest first; with $all, also those triggered or cancelled recently. */
    public function list(User $user, ?TradingAccount $account, bool $all = false): Collection
    {
        return $this->scoped($user, $account)
            ->when($all,
                fn ($query) => $query->where(fn ($query) => $query->where('status', PendingOrder::STATUS_PENDING)
                    ->orWhere('updated_at', '>=', now()->subDays(self::HISTORY_DAYS))),
                fn ($query) => $query->where('status', PendingOrder::STATUS_PENDING))
            ->with(['asset', 'tradingAccount'])
            ->latest()
            ->get();
    }

    public function cancel(User $user, ?TradingAccount $account, string $id): PendingOrder
    {
        return DB::transaction(function () use ($user, $account, $id) {
            $order = $this->lockedForUser($user, $account, $id);
            $this->close($order, PendingOrder::STATUS_CANCELLED, 'user');

            return $order;
        });
    }

    /** Sets or clears TP/SL on a waiting order; levels are checked against its pending price. */
    public function updateLevels(User $user, ?TradingAccount $account, string $id, ?float $takeProfit, ?float $stopLoss, bool $updateTakeProfit, bool $updateStopLoss): PendingOrder
    {
        return DB::transaction(function () use ($user, $account, $id, $takeProfit, $stopLoss, $updateTakeProfit, $updateStopLoss) {
            $order = $this->lockedForUser($user, $account, $id);
            $newTakeProfit = $updateTakeProfit ? (float) ($takeProfit ?? 0) : (float) $order->take_profit;
            $newStopLoss = $updateStopLoss ? (float) ($stopLoss ?? 0) : (float) $order->stop_loss;
            $this->assertLevels($order->trade_type, (float) $order->pending_price, $newTakeProfit, $newStopLoss);

            $order->update([
                'take_profit' => $newTakeProfit,
                'stop_loss' => $newStopLoss,
                'payload' => array_merge($order->payload ?? [], [
                    'take_profit' => $newTakeProfit ?: null, 'is_take_profit' => $newTakeProfit > 0,
                    'stop_loss' => $newStopLoss ?: null, 'is_stop_loss' => $newStopLoss > 0,
                ]),
            ]);

            return $order;
        });
    }

    /** Checks one client's waiting orders (run while their terminal polls). */
    public function checkUser(string $userId): int
    {
        return $this->check(PendingOrder::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('status', PendingOrder::STATUS_PENDING)
            ->with(['asset', 'user', 'tradingAccount', 'company'])
            ->get());
    }

    /** Checks the waiting orders on instruments whose price was just stored (PriceWatch). */
    public function checkAssets(array $assetIds): int
    {
        return $this->check(PendingOrder::withoutGlobalScopes()
            ->where('status', PendingOrder::STATUS_PENDING)
            ->whereIn('asset_id', $assetIds)
            ->with(['asset', 'user', 'tradingAccount', 'company'])
            ->get());
    }

    /** Checks every waiting order (the scheduled sweep, for clients who are offline). */
    public function checkAll(): int
    {
        $triggered = 0;
        PendingOrder::withoutGlobalScopes()
            ->where('status', PendingOrder::STATUS_PENDING)
            ->with(['asset', 'user', 'tradingAccount', 'company'])
            ->chunkById(200, function ($orders) use (&$triggered) {
                $triggered += $this->check($orders);
            });

        return $triggered;
    }

    /** Opens each order whose level the market has reached; returns how many opened. */
    private function check($orders): int
    {
        $refreshed = [];
        $triggered = 0;

        foreach ($orders as $order) {
            $asset = $order->asset;
            if ($asset && ! isset($refreshed[$asset->id]) && ($asset->price_age_seconds ?? PHP_INT_MAX) > self::PRICE_REFRESH_SECONDS) {
                (new AssetService)->updateRate($asset);
                $refreshed[$asset->id] = true;
            }

            // A closed market's price is its last tick, not a live one: the order waits for the open.
            if ($asset && ! MarketHours::status($asset)['open']) {
                continue;
            }

            // A missing asset or owner goes straight to trigger(), which cancels the order with the reason.
            if (! $asset || ! $order->user || $this->reached($order, $asset, $order->user)) {
                $triggered += (int) ($this->trigger($order) !== null);
            }
        }

        return $triggered;
    }

    /** Replays the order as a market order, or cancels it with the reason it can no longer open. */
    public function trigger(PendingOrder $order): ?Trade
    {
        $trade = DB::transaction(function () use ($order) {
            $locked = PendingOrder::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->first();
            if (! $locked?->isPending()) {
                return null;
            }
            $locked->setRelations($order->getRelations());

            return $this->asOwner($locked, fn () => $this->open($locked));
        });

        if ($trade) {
            rescue(fn () => broadcast(new TradeUpdated($order->user_id)), report: false);
        }

        return $trade;
    }

    private function open(PendingOrder $order): ?Trade
    {
        $user = $order->user;
        $account = $order->tradingAccount;
        $asset = $order->asset;

        if ($order->trading_account_id && (! $account || ! $account->is_active)) {
            return $this->cancelled($order, 'account_inactive');
        }
        if (! $user || ! $user->can_trade || $user->ai_trade) {
            return $this->cancelled($order, 'trading_disabled');
        }
        if (! $asset || ! $asset->is_active || ! $asset->is_provider_active) {
            return $this->cancelled($order, 'asset_inactive');
        }

        try {
            enforce_plan_limit('open_trades_max', Trade::query()->where('user_id', $user->id)->where('status', 0)->count());
            if ($account) {
                enforce_plan_limit('open_trades_per_account_max', Trade::query()->where('trading_account_id', $account->id)->where('status', 0)->count());
            }
        } catch (PlanLimitExceededException) {
            return $this->cancelled($order, 'plan_limit');
        }

        // The margin is worked out again at the fill price, not taken from when the order was placed.
        $payload = Arr::except($order->payload ?? [], ['margin']);
        $payload['user_id'] = $user->id;
        $payload['is_take_profit'] ??= false;
        $payload['is_stop_loss'] ??= false;

        $result = (new TradeService)->openTrade($payload);

        if (($result['status'] ?? 0) >= 1 && ($result['data'] ?? null) instanceof Trade) {
            $this->close($order, PendingOrder::STATUS_TRIGGERED, null, $result['data']->id);

            return $result['data'];
        }

        $message = (string) ($result['msg'] ?? '');
        $reason = match (true) {
            str_starts_with($message, 'Insufficient balance') => 'insufficient_margin',
            str_contains($message, 'not currently active') => 'asset_inactive',
            str_contains($message, 'can not trade') => 'trading_disabled',
            str_contains($message, 'select which trading account') => 'account_inactive',
            default => null,
        };

        if ($reason) {
            return $this->cancelled($order, $reason);
        }

        // Anything else (no usable price right now) is temporary: the order keeps waiting.
        Log::warning('pending_order.trigger_deferred', ['order' => $order->id, 'message' => $message]);

        return null;
    }

    /**
     * Runs $callback as the order's owner: their company, trading account and user, as the
     * request that placed it had. Restores whatever was bound before, since a queue worker and
     * the scheduler serve many owners in one process.
     */
    private function asOwner(PendingOrder $order, callable $callback): mixed
    {
        $app = app();
        $previous = [];
        foreach (['company', 'tradingAccount'] as $key) {
            $previous[$key] = $app->bound($key) ? $app->make($key) : null;
        }
        $previousUser = Auth::user();

        $order->company ? $app->instance('company', $order->company) : $app->forgetInstance('company');
        $order->tradingAccount ? $app->instance('tradingAccount', $order->tradingAccount) : $app->forgetInstance('tradingAccount');
        Auth::setUser($order->user);

        try {
            return $callback();
        } finally {
            foreach ($previous as $key => $value) {
                $value ? $app->instance($key, $value) : $app->forgetInstance($key);
            }
            $previousUser ? Auth::setUser($previousUser) : Auth::forgetUser();
        }
    }

    private function reached(PendingOrder $order, Asset $asset, User $user): bool
    {
        $price = (float) $order->pending_price;
        $ask = $asset->executionBuyPriceFor($user);
        $bid = $asset->executionSellPriceFor($user);

        return match ($order->order_type) {
            'buy_limit' => $ask > 0 && $ask <= $price,
            'buy_stop' => $ask > 0 && $ask >= $price,
            'sell_limit' => $bid > 0 && $bid >= $price,
            'sell_stop' => $bid > 0 && $bid <= $price,
            default => false,
        };
    }

    /** Buy fills at the ask, sell at the bid -- the same sides openTrade() uses. */
    private function marketPrice(Asset $asset, User $user, string $side): float
    {
        return $side === 'sell' ? $asset->executionSellPriceFor($user) : $asset->executionBuyPriceFor($user);
    }

    private function orderType(string $side, float $price, float $market): string
    {
        return $side === 'buy'
            ? ($price < $market ? 'buy_limit' : 'buy_stop')
            : ($price > $market ? 'sell_limit' : 'sell_stop');
    }

    /** A buy profits as price rises, so its TP sits above the entry and its SL below; a sell is the reverse. */
    private function assertLevels(string $side, float $price, float $takeProfit, float $stopLoss): void
    {
        if ($takeProfit > 0 && ($side === 'buy' ? $takeProfit <= $price : $takeProfit >= $price)) {
            $this->fail('take_profit', 'Take profit must be '.($side === 'buy' ? 'above' : 'below').' the pending price.');
        }
        if ($stopLoss > 0 && ($side === 'buy' ? $stopLoss >= $price : $stopLoss <= $price)) {
            $this->fail('stop_loss', 'Stop loss must be '.($side === 'buy' ? 'below' : 'above').' the pending price.');
        }
    }

    /** Margin the order would need if it filled at its own level (same formula as openTrade()'s fallback). */
    private function estimatedMargin(Asset $asset, float $volume, int $leverage, float $price): float
    {
        $atLevel = $asset->replicate();
        $atLevel->rate = $price;

        return $volume * ForexMarginRateResolver::marginRate($atLevel) / max($leverage, 1);
    }

    private function scoped(User $user, ?TradingAccount $account)
    {
        return PendingOrder::query()
            ->where('user_id', $user->id)
            ->when($account, fn ($query) => $query->where('trading_account_id', $account->id));
    }

    private function lockedForUser(User $user, ?TradingAccount $account, string $id): PendingOrder
    {
        $order = $this->scoped($user, $account)->whereKey($id)->lockForUpdate()->first();
        if (! $order) {
            $this->fail('id', 'Order not found.');
        }
        if (! $order->isPending()) {
            $this->fail('id', 'This order is no longer pending.');
        }

        return $order->load(['asset', 'tradingAccount']);
    }

    private function cancelled(PendingOrder $order, string $reason): null
    {
        $this->close($order, PendingOrder::STATUS_CANCELLED, $reason);

        return null;
    }

    private function close(PendingOrder $order, string $status, ?string $reason, ?string $tradeId = null): void
    {
        $order->update([
            'status' => $status,
            'cancel_reason' => $reason,
            'opened_trade_id' => $tradeId,
            'triggered_at' => $status === PendingOrder::STATUS_TRIGGERED ? now() : null,
            'cancelled_at' => $status === PendingOrder::STATUS_CANCELLED ? now() : null,
        ]);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
