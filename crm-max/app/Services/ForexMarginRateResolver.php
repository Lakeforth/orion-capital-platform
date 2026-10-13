<?php

namespace App\Services;

use App\Models\Asset;

/**
 * Required margin for a forex trade must be expressed in USD (the account
 * currency), not in whatever currency the instrument happens to be quoted
 * in. `Asset::rate` is fine to multiply directly only when the pair's quote
 * currency (`Asset::base`) is already USD (e.g. EURUSD, GBPUSD) -- for any
 * other pair (USD-base like USDJPY/USDCHF, or a true cross like EURJPY/
 * GBPJPY/EURGBP) multiplying by the raw rate produces a value in the wrong
 * currency entirely, off by whatever that currency's own exchange rate is
 * (e.g. ~150x too high for JPY crosses, since JPY rates run ~150-220).
 *
 * This resolves the correct USD multiplier per instrument: the USD value of
 * one unit of the instrument's base currency (`Asset::sym`).
 */
class ForexMarginRateResolver
{
    /** @var array<string, float>|null 1 unit of currency => its USD value */
    private static ?array $usdValuePerCurrency = null;

    public static function marginRate(Asset $asset): float
    {
        if ($asset->type !== 'forex') {
            // Every other category is already USD-denominated at the
            // instrument level (crypto/stocks/indices/commodities/metals) --
            // this resolver only exists for currency-pair cross-conversion.
            return (float) $asset->rate;
        }

        if ($asset->base === 'USD') {
            return (float) $asset->rate;
        }

        if ($asset->sym === 'USD') {
            // Base currency of the pair IS the account currency already --
            // volume is already denominated in USD, no conversion needed.
            return 1.0;
        }

        return self::usdValue($asset->sym) ?? (float) $asset->rate;
    }

    /**
     * USD value of one unit of the instrument's QUOTE currency (`Asset::base`).
     *
     * Realised and unrealised P&L come out in the quote currency -- price
     * difference x volume is denominated in whatever the instrument is quoted
     * in -- so a EURJPY position's P&L is yen, an AU200/AUD position's is
     * Australian dollars. Multiplying by this converts to the account
     * currency. Note this is the QUOTE side, the mirror of marginRate(),
     * which converts from the BASE side because margin is a base-currency
     * notional.
     *
     * Falls back to 1.0 (no conversion, i.e. today's behaviour) for a quote
     * currency no forex pair prices against USD -- XAU/XAG is the live
     * example. Returning a wrong number would be worse than returning the
     * number we already return.
     */
    public static function quoteToUsd(Asset $asset): float
    {
        if ($asset->base === 'USD') {
            return 1.0;
        }

        return self::usdValue((string) $asset->base) ?? 1.0;
    }

    /**
     * USD value of one unit of the given currency, resolved from whichever
     * other forex asset happens to quote it against USD -- either directly
     * (sym=EUR, base=USD -> rate IS the USD value) or inverted (sym=USD,
     * base=CHF -> 1/rate is CHF's USD value, since CHF/CAD/JPY/etc. are
     * conventionally quoted as USD/X, not X/USD).
     */
    private static function usdValue(string $currency): ?float
    {
        self::$usdValuePerCurrency ??= self::loadUsdValues();

        return self::$usdValuePerCurrency[$currency] ?? null;
    }

    /** @return array<string, float> */
    private static function loadUsdValues(): array
    {
        $map = ['USD' => 1.0];

        Asset::query()
            ->where('type', 'forex')
            ->where('is_provider_active', 1)
            ->get(['sym', 'base', 'rate'])
            ->each(function (Asset $asset) use (&$map) {
                $rate = (float) $asset->rate;

                if ($rate <= 0) {
                    return;
                }

                if ($asset->base === 'USD' && ! isset($map[$asset->sym])) {
                    $map[$asset->sym] = $rate;
                } elseif ($asset->sym === 'USD' && ! isset($map[$asset->base])) {
                    $map[$asset->base] = 1 / $rate;
                }
            });

        return $map;
    }

    /**
     * Tests / long-running workers (queue jobs) must not reuse a stale rate
     * snapshot across requests -- call this if resolving margin rates
     * outside a fresh request lifecycle.
     */
    public static function flush(): void
    {
        self::$usdValuePerCurrency = null;
    }
}
