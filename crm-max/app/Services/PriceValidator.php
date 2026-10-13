<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The single gate every write to Asset.rate has to pass through.
 *
 * Before this existed, each update path made its own (or no) decision about
 * whether an incoming price was usable: the bulk feed sync had a flat 20%
 * deviation guard, the OANDA forex job had a bare "> 0", getRate() had
 * nothing at all, and the daily asset sync wrote whatever it was handed. So
 * "is this price sane" depended entirely on which code path happened to be
 * carrying it, and a value rejected by one writer could be accepted a second
 * later by another.
 *
 * Nothing here talks to the database or to an HTTP client -- it is a pure
 * decision over (previous state, incoming quote), which is what lets the
 * bulk path evaluate 234 assets from one preloaded snapshot and the on-demand
 * path evaluate a single asset, with identical rules.
 */
class PriceValidator
{
    /**
     * Rejection reasons, in the order they are checked. Emitted verbatim in
     * the log line so a rejection can be counted/grepped by kind rather than
     * by reading prose.
     */
    public const REASON_NON_POSITIVE = 'non_positive';

    public const REASON_DUPLICATE = 'duplicate_tick';

    public const REASON_OUT_OF_ORDER = 'out_of_order';

    public const REASON_STALE_TICK = 'stale_provider_tick';

    public const REASON_OUTLIER = 'outlier';

    /**
     * How old the *stored* price may be before it stops being a trustworthy
     * baseline to compare a new tick against. Confirmed live during the
     * original plausibility-guard rollout: assets left untouched through a
     * dormant-scheduler period legitimately differ from a fresh quote by far
     * more than any deviation bound, and rejecting those forever freezes them
     * on a wrong price -- which is worse than the bug the bound exists to
     * prevent. Past this age any positive quote is accepted outright.
     */
    public const STALENESS_GRACE_MINUTES = 10;

    /**
     * How far behind the provider's own clock a quote may be and still be
     * treated as live. This catches a delayed snapshot -- a response that is
     * newer than what we hold but describes a market state from a while ago.
     * A frozen feed (closed market re-sending the same tick) never reaches
     * this check; it is caught as a duplicate first.
     */
    public const MAX_PROVIDER_TICK_AGE_MINUTES = 15;

    /**
     * Deviation a single tick may move, as a fraction of the previous price,
     * per instrument class. A flat threshold for everything is what QA
     * specifically asked us to get away from: 20% is far too loose for a
     * forex major (which barely moves 1% in a day) and roughly right for
     * crypto, so one number is simultaneously useless at one end and
     * necessary at the other.
     */
    private const DEVIATION_BY_TYPE = [
        'forex' => 0.02,
        'metals' => 0.05,
        'indices' => 0.05,
        'commodities' => 0.08,
        'stocks' => 0.15,
        'crypto' => 0.20,
    ];

    private const DEFAULT_DEVIATION = 0.20;

    /**
     * A move is never rejected for being smaller than this many ticks of the
     * instrument's own quoting precision. On a coarsely-quoted, very
     * low-priced instrument a single legitimate tick can be a large
     * percentage of the price, so a percentage bound alone would reject
     * ordinary movement.
     */
    private const MIN_ALLOWED_TICKS = 20;

    /**
     * Significant figures a quote is assumed to carry when the instrument has
     * no known fixed pip size. Used only to derive the floor above -- the
     * authoritative per-pair precision (OANDA pipLocation, CEX.IO
     * pricePrecision) is not stored on the asset today; if it ever is, feed
     * it in here instead of inferring from magnitude.
     */
    private const ASSUMED_SIGNIFICANT_DIGITS = 6;

    /**
     * @param  object  $current  previous state: rate, price_updated_at, price_tick_at,
     *                           type, base, price_high, price_low. An Asset model or any
     *                           row object with those attributes.
     * @param  float  $newPrice  the incoming quote
     * @param  CarbonInterface|null  $tickAt  the provider's own timestamp for that quote, if it sends one
     */
    public function check(object $current, float $newPrice, ?CarbonInterface $tickAt = null): PriceCheck
    {
        $currentRate = (float) ($current->rate ?? 0);

        if (! is_finite($newPrice) || $newPrice <= 0) {
            return PriceCheck::reject(self::REASON_NON_POSITIVE, [
                'rejected_price' => $newPrice,
                'current_rate' => $currentRate,
            ]);
        }

        // Ordering is checked before staleness on purpose: a market that has
        // closed keeps re-sending its last tick forever, and that is a
        // duplicate (nothing to write, nothing to warn about), not a stale
        // feed.
        $storedTickAt = $this->timestamp($current, 'price_tick_at');

        if ($tickAt !== null && $storedTickAt !== null) {
            if ($tickAt->equalTo($storedTickAt)) {
                return PriceCheck::reject(self::REASON_DUPLICATE, [
                    'tick_at' => $tickAt->toIso8601String(),
                ], shouldLog: false);
            }

            if ($tickAt->lessThan($storedTickAt)) {
                return PriceCheck::reject(self::REASON_OUT_OF_ORDER, [
                    'rejected_price' => $newPrice,
                    'current_rate' => $currentRate,
                    'rejected_tick_at' => $tickAt->toIso8601String(),
                    'stored_tick_at' => $storedTickAt->toIso8601String(),
                    'behind_by_seconds' => $storedTickAt->diffInSeconds($tickAt, absolute: true),
                ]);
            }
        }

        if ($tickAt !== null && $tickAt->lessThan(now()->subMinutes(self::MAX_PROVIDER_TICK_AGE_MINUTES))) {
            return PriceCheck::reject(self::REASON_STALE_TICK, [
                'rejected_price' => $newPrice,
                'tick_at' => $tickAt->toIso8601String(),
                'age_minutes' => $tickAt->diffInMinutes(now(), absolute: true),
            ]);
        }

        if (! $this->hasTrustworthyBaseline($current, $currentRate)) {
            return PriceCheck::accept();
        }

        $allowedMove = $this->allowedMove($current, $currentRate, $newPrice);
        $actualMove = abs($newPrice - $currentRate);

        if ($actualMove > $allowedMove) {
            return PriceCheck::reject(self::REASON_OUTLIER, [
                'rejected_price' => $newPrice,
                'current_rate' => $currentRate,
                'move' => round($actualMove, 8),
                'allowed_move' => round($allowedMove, 8),
                'deviation_pct' => round($actualMove / $currentRate * 100, 4),
            ]);
        }

        return PriceCheck::accept();
    }

    /**
     * Logs a rejection against the dedicated price channel so QA can read
     * every discarded tick in one file instead of picking them out of the
     * general application log.
     */
    public function logRejection(object $current, PriceCheck $check, string $source): void
    {
        if (! $check->shouldLog) {
            return;
        }

        // The feed repeats a refused tick on every poll (a closed market keeps sending its last
        // one), so each refused tick is logged once a day rather than on every poll.
        $tick = $check->context['tick_at'] ?? $check->context['rejected_tick_at'] ?? $check->context['rejected_price'] ?? null;
        if ($tick !== null && ! Cache::add('price_rejection:'.$check->reason.':'.($current->id ?? $current->coin_id ?? '').':'.$tick, true, now()->addDay())) {
            return;
        }

        Log::channel('price')->warning('Rejected price update: '.$check->reason, array_merge([
            'reason' => $check->reason,
            'source' => $source,
            'asset_id' => $current->id ?? null,
            'symbol' => $current->sym_key ?? ($current->sym ?? null),
            'coin_id' => $current->coin_id ?? null,
            'type' => $current->type ?? null,
        ], $check->context));
    }

    /**
     * A previous price is only worth comparing against if we know it was
     * itself set recently. price_updated_at is the right field for that
     * (updated_at moves on any unrelated admin edit); rows migrated before
     * that column existed fall back to it.
     */
    private function hasTrustworthyBaseline(object $current, float $currentRate): bool
    {
        if ($currentRate <= 0) {
            return false;
        }

        $pricedAt = $this->timestamp($current, 'price_updated_at') ?? $this->timestamp($current, 'updated_at');

        return $pricedAt !== null && $pricedAt->greaterThan(now()->subMinutes(self::STALENESS_GRACE_MINUTES));
    }

    /**
     * The widest single-tick move this instrument may make, taking whichever
     * of three instrument-aware measures is most permissive:
     *
     *  - its class's own percentage bound (a forex major and a small-cap
     *    stock do not deserve the same number);
     *  - a floor of a few ticks of its quoting precision, so a coarsely
     *    quoted instrument is not rejected for moving one tick;
     *  - its own 24h high/low range, which is the most direct evidence
     *    available of what "normal movement" means for this instrument right
     *    now -- a price it has actually traded at today is not an outlier.
     */
    private function allowedMove(object $current, float $currentRate, float $newPrice): float
    {
        $type = (string) ($current->type ?? '');
        $percentBound = (self::DEVIATION_BY_TYPE[$type] ?? self::DEFAULT_DEVIATION) * $currentRate;

        $tickFloor = self::MIN_ALLOWED_TICKS * $this->tickSize($current, $currentRate);

        return max($percentBound, $tickFloor, $this->recentRange($current, $currentRate));
    }

    private function recentRange(object $current, float $currentRate): float
    {
        $high = (float) ($current->price_high ?? 0);
        $low = (float) ($current->price_low ?? 0);

        if ($high <= 0 || $low <= 0 || $high <= $low) {
            return 0.0;
        }

        $range = $high - $low;

        // A 24h range wider than the price itself means the range fields are
        // themselves junk (or the instrument is in a state no bound can
        // sensibly describe); don't let bad reference data widen the gate.
        return $range >= $currentRate ? 0.0 : $range;
    }

    /**
     * Smallest meaningful price increment for this instrument. Forex and
     * metals have fixed, provider-published pip sizes; everything else is
     * inferred from the order of magnitude of the price, which is how these
     * feeds quote in practice (a handful of significant figures regardless of
     * scale).
     *
     * Deliberately separate from Asset::pipPrecision(), which feeds the
     * spread formula and is mid-revision elsewhere -- validation bounds and
     * pricing must not become coupled through a shared constant. (The
     * unresolved gold pip-size question between OANDA's 0.01 and QA's 0.1 has
     * no bearing here: either value produces a floor far below the
     * percentage bound.)
     */
    private function tickSize(object $current, float $price): float
    {
        $type = (string) ($current->type ?? '');

        if ($type === 'forex') {
            return strtoupper((string) ($current->base ?? '')) === 'JPY' ? 0.01 : 0.0001;
        }

        if ($type === 'metals' || $type === 'commodities') {
            return 0.01;
        }

        if ($price <= 0) {
            return 0.0;
        }

        $exponent = (int) floor(log10($price)) - (self::ASSUMED_SIGNIFICANT_DIGITS - 1);

        return 10 ** max($exponent, -12);
    }

    private function timestamp(object $current, string $attribute): ?CarbonInterface
    {
        $value = $current->{$attribute} ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
