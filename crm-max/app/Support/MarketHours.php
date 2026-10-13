<?php

namespace App\Support;

use App\Models\Asset;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Whether an instrument's market is open now, and when it next opens.
 *
 * The price provider's own session decides while it is recent: open only when "tradeable".
 * Crypto has no session and never closes. Without a recent session a weekly schedule in New
 * York time stands in: stocks trade 09:30-16:00 on weekdays; forex, metals, commodities and
 * indices from Sunday 17:00 to Friday 17:00. Holidays and halts are only known from the provider.
 */
class MarketHours
{
    /** A provider session older than this no longer decides; the schedule does. */
    public const SESSION_FRESH_SECONDS = 600;

    private const NEW_YORK = 'America/New_York';

    /** @return array{open: bool, next_open_at: ?CarbonImmutable} */
    public static function status(Asset $asset, ?CarbonInterface $now = null): array
    {
        $now = CarbonImmutable::instance($now ?? now())->utc();

        if ($asset->type === 'crypto') {
            return ['open' => true, 'next_open_at' => null];
        }

        $scheduledOpen = self::nextScheduledOpen($asset->type, $now);
        $sessionAt = $asset->market_session_at;

        if ($asset->market_session !== null && $sessionAt && $sessionAt->greaterThan($now->subSeconds(self::SESSION_FRESH_SECONDS))) {
            $open = $asset->market_session === 'tradeable';

            // Closed by the provider while the schedule has it open (a holiday, a halt): no known reopening.
            return ['open' => $open, 'next_open_at' => $open ? null : $scheduledOpen];
        }

        return ['open' => $scheduledOpen === null, 'next_open_at' => $scheduledOpen];
    }

    /** The refusal shown when an order is placed on a closed market, or null while it is open. */
    public static function closedMessage(Asset $asset, ?CarbonInterface $now = null): ?string
    {
        ['open' => $open, 'next_open_at' => $nextOpen] = self::status($asset, $now);

        if ($open) {
            return null;
        }

        return "The market for {$asset->sy} is closed.".($nextOpen ? ' It opens '.$nextOpen->format('D H:i').' UTC.' : '');
    }

    /** The schedule's next opening after $now (UTC), or null while the schedule has the market open. */
    public static function nextScheduledOpen(?string $type, CarbonImmutable $now): ?CarbonImmutable
    {
        $local = $now->setTimezone(self::NEW_YORK);

        if (in_array($type, ['stocks', 'stock'], true)) {
            for ($day = 0; $day <= 7; $day++) {
                $date = $local->startOfDay()->addDays($day);
                if ($date->isWeekend()) {
                    continue;
                }

                $opens = $date->setTime(9, 30);
                if ($day === 0 && $local->greaterThanOrEqualTo($opens) && $local->lessThan($date->setTime(16, 0))) {
                    return null;
                }
                if ($opens->greaterThan($local)) {
                    return $opens->utc();
                }
            }

            return null;
        }

        $minutes = $local->hour * 60 + $local->minute;
        $closed = $local->isSaturday()
            || ($local->isFriday() && $minutes >= 17 * 60)
            || ($local->isSunday() && $minutes < 17 * 60);

        if (! $closed) {
            return null;
        }

        $sunday = $local->isSunday() ? $local->startOfDay() : $local->next(CarbonInterface::SUNDAY);

        return $sunday->setTime(17, 0)->utc();
    }
}
