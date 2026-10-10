<?php

namespace App\Support\LeadMax;

use App\Models\Company;

/**
 * Which instruments a tenant has switched off.
 *
 * The assets table is shared: 264 instruments, no company_id, one row per symbol for all of
 * the brokers on this install. So "make this symbol inactive" cannot mean flipping
 * assets.is_active -- that would take the symbol away from every other broker too.
 *
 * This stores the symbols a company has turned OFF, not the ones it has on. That matters:
 *
 *  - Empty means everything is on, so switching the feature on changes nothing for anybody
 *    until an admin actually turns something off.
 *  - A new instrument added to the catalogue is live for every tenant immediately, rather
 *    than being invisible until each of them is told to opt in.
 *
 * The company_assets table looks like it already does this, and is deliberately not used.
 * CompanyCreatedJob seeds it with every asset id that exists at the moment the company is
 * created and nothing maintains it afterwards, so a tenant that signed up before the
 * catalogue grew holds a short list. 24 of the 60 rows on production are short that way.
 * Reading it would have removed symbols from two dozen live trade rooms on the strength of
 * when each broker happened to sign up. Nothing has ever read that table.
 */
class CompanyAssets
{
    public const SETTING = 'leadmax_disabled_assets';

    /** Asset ids this company has switched off. Empty means it has switched nothing off. */
    public static function disabled(?Company $company = null): array
    {
        $company = $company ?: self::current();

        if (! $company) {
            return [];
        }

        $stored = $company->setting(self::SETTING);

        return is_array($stored) ? array_values(array_filter($stored, 'is_string')) : [];
    }

    public static function isEnabled(string $assetId, ?Company $company = null): bool
    {
        return ! in_array($assetId, self::disabled($company), true);
    }

    /**
     * Flip one instrument for one company and return its new state.
     *
     * Returns true when the instrument is now on.
     */
    public static function toggle(string $assetId, ?Company $company = null): bool
    {
        $company = $company ?: self::current();

        if (! $company) {
            return true;
        }

        $disabled = self::disabled($company);
        $wasOff = in_array($assetId, $disabled, true);

        $disabled = $wasOff
            ? array_values(array_diff($disabled, [$assetId]))
            : [...$disabled, $assetId];

        $settings = $company->settingsArray();
        $settings[self::SETTING] = array_values(array_unique($disabled));
        $company->settings = $settings;
        $company->save();

        // Settings are cached per company and per slug; without this the trade room keeps
        // serving the old list until the cache happens to expire.
        Company::clearCache($company);

        return $wasOff;
    }

    private static function current(): ?Company
    {
        return app()->bound('company') ? app('company') : null;
    }
}
