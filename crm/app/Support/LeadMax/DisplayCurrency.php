<?php

namespace App\Support\LeadMax;

use App\Models\Currency;
use Illuminate\Support\Collection;

/**
 * The currency amounts are shown in.
 *
 * Amounts are stored in the base currency (USD, the row with is_default and rate 1.0); every
 * other row's rate is how many of it one base unit buys. Picking a currency only changes how
 * figures are rendered for the person who picked it — nothing stored is converted or rewritten.
 *
 * Kept per person -- in the session and on that user's own account settings -- so one admin
 * switching to EUR to read a report does not change what anyone else sees.
 */
class DisplayCurrency
{
    public const SESSION_KEY = 'leadmax_display_currency';

    /**
     * The currencies offered in the switcher: base first, then alphabetical.
     *
     * is_asset rows are trading instruments (BTC) rather than display currencies, so they are
     * left out — quoting a client balance in bitcoin is not what the control is for.
     */
    public static function options(): Collection
    {
        return once(fn () => rescue(
            fn () => Currency::query()
                ->where(fn ($q) => $q->whereNull('is_asset')->orWhere('is_asset', 0))
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->get(),
            collect(),
            report: false
        ));
    }

    /** The selected currency, or null to use the configured default. */
    public static function current(): ?Currency
    {
        // This session's choice first, then the one saved on the user's account (Account Settings).
        $code = rescue(fn () => session(self::SESSION_KEY) ?? data_get(auth()->user()?->settings, 'leadmax.currency'), null, report: false);

        if (blank($code)) {
            return null;
        }

        return self::options()->firstWhere('code', $code);
    }

    /** Remember a choice. An unknown or blank code clears it, falling back to the default. */
    public static function set(?string $code): void
    {
        $match = blank($code) ? null : self::options()->firstWhere('code', $code);

        $match ? session([self::SESSION_KEY => $match->code]) : session()->forget(self::SESSION_KEY);

        // Also kept on the signed-in user, so the header and Account Settings share one choice
        // and it survives signing in on another browser. Still per user, never tenant-wide.
        if ($user = rescue(fn () => auth()->user(), null, report: false)) {
            $settings = $user->settings ?? [];
            data_set($settings, 'leadmax.currency', $match?->code);
            $user->forceFill(['settings' => $settings])->saveQuietly();
        }
    }

    public static function code(): string
    {
        return self::current()?->code ?? 'USD';
    }

    /**
     * The symbol to prefix amounts with.
     *
     * Falls back to the configured symbol so that with nothing selected — and on any request
     * without a session, such as a queue worker or the API — this behaves exactly as before.
     */
    public static function symbol(): string
    {
        return self::current()?->symbol ?: env('CURRENCY_SYMBOL', '$');
    }

    /** Convert a base-currency amount for display. */
    public static function convert(float $amount): float
    {
        $rate = (float) (self::current()?->rate ?? 1);

        return $rate > 0 ? $amount * $rate : $amount;
    }
}
