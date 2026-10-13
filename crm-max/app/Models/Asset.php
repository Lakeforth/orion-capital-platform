<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class Asset extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'ex_sym',
        'sym',
        'rate',
        'volume',
        'market_cap',
        'change',
        'change_percent',
        'buy_spread',
        'position',
        'sell_spread',
        'base',
        'leverage',
        'is_active',
        'is_provider_active',
        'image',
        'sym_key',
        'type',
        'featured',
        'price_high',
        'price_low',
        'is_live_market', 'live_market_end_time', 'est_profit',
    ];

    protected $appends = ['sy', 'buy_price', 'sell_price', 'expires_at', 'price_age_seconds'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_provider_active' => 'boolean',
        'price_updated_at' => 'datetime',
        'price_tick_at' => 'datetime',
        'market_session_at' => 'datetime',
    ];

    /**
     * Seconds since this asset last accepted a validated price. Serialized
     * with the asset so both the trade room and the back office can tell a
     * live price from one that simply hasn't moved -- updated_at can't answer
     * that, it shifts on any unrelated admin edit.
     */
    public function getPriceAgeSecondsAttribute(): ?int
    {
        $pricedAt = $this->price_updated_at ?? $this->updated_at;

        return $pricedAt ? (int) $pricedAt->diffInSeconds(now(), absolute: true) : null;
    }

    public function scopeTradable($query)
    {
        return $query->where('is_active', true)->where('is_provider_active', true);
    }

    /**
     * Narrow to what one tenant actually offers.
     *
     * Deliberately separate from tradable(): that one is about the platform and the price
     * feed, and is read by jobs and price updaters that have no company in scope. This is
     * about one broker's own choices, so it no-ops when no company is resolved -- an
     * unscoped caller sees exactly what it saw before.
     */
    public function scopeEnabledForCompany($query, $company = null)
    {
        $disabled = \App\Support\LeadMax\CompanyAssets::disabled($company);

        return $disabled === [] ? $query : $query->whereNotIn('id', $disabled);
    }

    public function getSyAttribute(): string
    {
        return $this->sym.'/'.$this->base;
    }

    public function getLeverageAttribute()
    {
        $leverage = 10;

        $leverage_system = company_settings('leverage_system', 'plan_based');

        $data = [
            'cryptos' => $leverage_system == 'asset_category_based' ? company_settings('cryptos_leverage', 10) : $leverage,
            'stocks' => $leverage_system == 'asset_category_based' ? company_settings('stocks_leverage', 20) : $leverage,
            'indices' => $leverage_system == 'asset_category_based' ? company_settings('indices_leverage', 20) : $leverage,
            'commodities' => $leverage_system == 'asset_category_based' ? company_settings('commodities_leverage', 10) : $leverage,
            'metals' => $leverage_system == 'asset_category_based' ? company_settings('metals_leverage', 10) : $leverage,
            'forex' => $leverage_system == 'asset_category_based' ? company_settings('forex_leverage', 10) : $leverage,
        ];

        if ($this->type == 'crypto') {
            return $data['cryptos'];
        }
        if ($this->type == 'stocks') {
            return $data['stocks'];
        }
        if ($this->type == 'indices') {
            return $data['indices'];
        }
        if ($this->type == 'commodities') {
            return $data['commodities'];
        }
        if ($this->type == 'metals') {
            return $data['metals'];
        }
        if ($this->type == 'forex') {
            return $data['forex'];
        }

        return $leverage;
    }

    public function getBuyPriceAttribute()
    {
        return $this->buyPriceFor(auth()->user());
    }

    public function getSellPriceAttribute()
    {
        return $this->sellPriceFor(auth()->user());
    }

    /**
     * Same as the buy_price accessor, but for an explicit user rather than
     * auth()->user() -- needed anywhere trade entry/PnL is computed outside
     * an authenticated request (queue jobs like UpdateTradeProfitJob, admin
     * closing a customer's trade) so that customer's spread_multiplier still
     * applies instead of silently falling back to 1.0.
     */
    /**
     * QA-confirmed (2026-08-10): an unconfigured customer (no admin has
     * ever touched their spread_multiplier) still pays normal market
     * spread, same as multiplier=1 -- there's no "opt-in to spread" state,
     * only "opt into something OTHER than normal" (a higher multiplier to
     * charge more, or an explicit 0 to waive it for a specific customer).
     * getUserSpreadMultiplier() already defaults an unset multiplier to
     * 1.0 -- only gate here on there being no user at all (nobody to
     * charge), not on the multiplier itself being null.
     */
    public function buyPriceFor(?User $user): float
    {
        if (! $user) {
            return $this->rate;
        }

        return round($this->rate + $this->spreadAdjustment('buy', $user), 6);
    }

    /**
     * Legacy tenants keep the QA-confirmed one-sided mechanics from
     * 2026-08-10: buy carries the configured cost and sell is the raw rate.
     * Tenants using two_sided_percentage get a true bid below market here,
     * using their category's *_sell_spread setting.
     */
    public function sellPriceFor(?User $user): float
    {
        if (! $user || ! $this->usesTwoSidedPercentageSpread()) {
            return $this->rate;
        }

        return round($this->rate - $this->spreadAdjustment('sell', $user), 6);
    }

    /**
     * Preserve the established one-sided/pip behaviour by default. A tenant
     * must explicitly opt into percentage adjustments on both price sides.
     */
    protected function usesTwoSidedPercentageSpread(): bool
    {
        return company_settings('spread_pricing_model', 'legacy_one_sided') === 'two_sided_percentage';
    }

    protected function percentageSpreadAdjustment(string $direction, User $user): float
    {
        $category = $this->getSpreadCategoryKey();
        $rawSpread = (float) company_settings("{$category}_{$direction}_spread", 0);
        $multiplier = $this->getUserSpreadMultiplier($user);
        $adjustment = ($rawSpread * $multiplier * $this->rate) / 100;
        $cap = self::MAX_SPREAD_ADJUSTMENT_PCT * $this->rate;

        return max(min($adjustment, $cap), -$cap);
    }

    /**
     * Ceiling on the percent-of-price formula's own output, as a fraction of
     * the current rate. Confirmed live (2026-08-18, Broker Rader): a raw
     * spread setting of 30 -- an entirely normal-looking value an admin would
     * enter thinking in points, same shape as the values already flagged
     * dangerous for spread_multiplier -- reads as 30% under this formula and
     * wiped a real customer's balance on a single SPX500 trade with zero real
     * price movement. This is the same class of bug as MAX_SPREAD_MULTIPLIER
     * below (a plausible-looking admin value blowing up an unbounded
     * formula), just triggered by the raw spread setting instead of the
     * multiplier. No legitimate bid/ask spread is anywhere near this wide;
     * capping here means indices/commodities/stocks/crypto (the categories
     * still on the percent-of-price formula, per the doc comment on
     * spreadAdjustment() below) can never move a customer's entry price by
     * more than 2% regardless of what gets entered in company_settings.
     */
    protected const MAX_SPREAD_ADJUSTMENT_PCT = 0.02;

    /**
     * Forex/gold spreads are quoted in pips -- a fixed increment
     * independent of the instrument's own price -- not a percentage of
     * price. Confirmed live: treating the raw spread setting as a
     * percent-of-price (the old formula, still used below for anything
     * without a confirmed pip size) blew up for high-value instruments
     * like gold, and went outright negative once a spread_multiplier was
     * layered on top. Only forex and gold have a QA-confirmed pip size as
     * of 2026-08-10 -- silver (shares gold's "commodities"/"metals"
     * category but is a different instrument), indices, crypto, and
     * stocks stay on the old percent-of-price formula until confirmed.
     */
    protected function spreadAdjustment(string $direction, User $user): float
    {
        if ($this->usesTwoSidedPercentageSpread()) {
            return $this->percentageSpreadAdjustment($direction, $user);
        }

        $category = $this->getSpreadCategoryKey();
        $rawSpread = (float) company_settings("{$category}_{$direction}_spread", 0);
        $multiplier = $this->getUserSpreadMultiplier($user);
        $pip = $this->pipPrecision();

        if ($pip > 0) {
            return $rawSpread * $pip * $multiplier;
        }

        $adjustment = ($rawSpread * $multiplier * $this->rate) / 100;
        $cap = self::MAX_SPREAD_ADJUSTMENT_PCT * $this->rate;

        return max(min($adjustment, $cap), -$cap);
    }

    protected function pipPrecision(): float
    {
        if ($this->type === 'forex') {
            return $this->base === 'JPY' ? 0.01 : 0.0001;
        }

        if ($this->sym === 'XAU') {
            return 0.1;
        }

        return 0;
    }

    /**
     * Decimal places to display this instrument's price at. QA-specified
     * table (2026-08-19): forex 5dp, JPY pairs 3dp, gold 2dp, silver 3dp,
     * commodities 2dp except natural gas at 3dp, indices/stocks 2dp, crypto
     * 2dp above $100 else 4dp. Single source of truth so the back office,
     * client area and web trader can't disagree on precision -- the same
     * mismatch already reported once for raw price values.
     */
    public function displayPrecision(): int
    {
        if ($this->sym === 'XAU') {
            return 2;
        }

        if ($this->sym === 'XAG') {
            return 3;
        }

        if ($this->sym === 'NATGAS') {
            return 3;
        }

        if ($this->type === 'forex') {
            return $this->base === 'JPY' ? 3 : 5;
        }

        if ($this->type === 'crypto') {
            return $this->rate >= 100 ? 2 : 4;
        }

        if (in_array($this->type, ['stocks', 'indices', 'commodities', 'metals'], true)) {
            return 2;
        }

        return 5;
    }

    public function executionBuyPriceFor(?User $user): float
    {
        return $this->buyPriceFor($user);
    }

    /**
     * Used for buy-exit and sell-entry (see openTrade()/updateTradeProfit()
     * in TradeService). This remains raw for legacy tenants and becomes the
     * tenant's bid price when two-sided percentage pricing is enabled.
     */
    public function executionSellPriceFor(?User $user): float
    {
        return $this->sellPriceFor($user);
    }

    /**
     * Was a falsy check ($user->spread_multiplier ? ... : 1.0), which
     * treated an explicit 0 the same as never-configured -- silently
     * overriding an admin's intentional "waive spread for this customer"
     * to normal spread instead. Only an unset (null) multiplier should
     * default to 1.0; an explicit 0 must stay 0.
     *
     * Clamped at MAX_SPREAD_MULTIPLIER as a second line of defense --
     * the admin form now validates this on input, but a stray value
     * reaching the column any other way (tinker, a migration, direct
     * SQL) has twice fed the crypto/commodities percent-of-price spread
     * formula an execution price large enough to wipe a real customer's
     * balance via margin call (100, then 100000 -- see MarginCall
     * history). Clamping here means the formula itself can never blow
     * up regardless of how a bad value got into the column.
     */
    protected const MAX_SPREAD_MULTIPLIER = 20.0;

    protected function getUserSpreadMultiplier(?User $user = null): float
    {
        $user = $user ?? auth()->user();

        if (! $user || $user->spread_multiplier === null) {
            return 1.0;
        }

        return min((float) $user->spread_multiplier, self::MAX_SPREAD_MULTIPLIER);
    }

    protected function getSpreadCategoryKey(): string
    {
        if ($this->type === 'crypto') {
            return 'cryptos';
        }

        if ($this->type === 'stock') {
            return 'stocks';
        }

        return (string) $this->type;
    }

    public function getExpiresAtAttribute(): int
    {

        $currentTime = now();
        $marketEndTime = Carbon::parse($this->live_market_end_time);

        //        Log::info('Time calculation', [
        //            'current_time' => $currentTime->toDateTimeString(),
        //            'market_end_time' => $marketEndTime->toDateTimeString(),
        //            'difference_ms' => $currentTime->diffInMilliseconds($marketEndTime)
        //        ]);

        if ($marketEndTime->gt($currentTime)) {
            return $currentTime->diffInMilliseconds($marketEndTime);
        }

        return 0;

    }

    public function getImageAttribute($value)
    {
        $colors = ['E91E63', '9C27B0', '673AB7', '3F51B5', '0D47A1', '01579B', '00BCD4', '009688', '33691E', '1B5E20', '33691E', '827717', 'E65100',  'E65100', '3E2723', 'F44336', '212121'];
        $background = $colors[mt_rand(0, 16)];

        return 'https://ui-avatars.com/api/?size=256&background='.$background.'&color=fff&name='.urlencode($this->sym);
    }

    public function togglePosition($direction)
    {
        // Validate direction
        if (! in_array($direction, ['up', 'down'])) {
            throw new InvalidArgumentException('Direction must be "up" or "down".');
        }

        // Start a transaction
        DB::transaction(function () use ($direction) {
            // Get the current position
            $currentPosition = $this->position;

            if ($direction === 'up') {
                // Move up by decreasing position
                $newPosition = $currentPosition - 1;

                // Ensure the new position is not negative
                if ($newPosition < 0) {
                    return; // Prevent moving to a negative position
                }
            } else {
                // Move down by increasing position
                $newPosition = $currentPosition + 1;
            }

            // Update the item's position
            $this->position = $newPosition;
            $this->save();
        });
    }
}
