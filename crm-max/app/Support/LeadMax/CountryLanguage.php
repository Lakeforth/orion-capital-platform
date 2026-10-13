<?php

namespace App\Support\LeadMax;

/**
 * The CRM language (one of the 12 in the language dropdown) to pre-select for a visitor's
 * country, read from their IP. Only countries where one of those languages is clearly the
 * main one are listed; everywhere else -- and multilingual countries such as Belgium,
 * Switzerland or Canada -- stays English. A saved choice always wins over this.
 */
class CountryLanguage
{
    public const LANGUAGES = [
        'ar' => ['AE', 'BH', 'DZ', 'EG', 'IQ', 'JO', 'KW', 'LB', 'LY', 'MA', 'MR', 'OM', 'PS', 'QA', 'SA', 'SD', 'SY', 'TN', 'YE'],
        'de' => ['AT', 'DE', 'LI'],
        'es' => ['AR', 'BO', 'CL', 'CO', 'CR', 'CU', 'DO', 'EC', 'ES', 'GQ', 'GT', 'HN', 'MX', 'NI', 'PA', 'PE', 'PR', 'PY', 'SV', 'UY', 'VE'],
        'fr' => ['BF', 'BI', 'BJ', 'CD', 'CF', 'CG', 'CI', 'DJ', 'FR', 'GA', 'GN', 'HT', 'KM', 'MC', 'MG', 'ML', 'NE', 'SN', 'TD', 'TG'],
        'hi' => ['IN'],
        'it' => ['IT', 'SM', 'VA'],
        'nl' => ['NL', 'SR'],
        'pt' => ['AO', 'BR', 'CV', 'GW', 'MZ', 'PT', 'ST', 'TL'],
        'ru' => ['BY', 'KG', 'KZ', 'RU'],
        'tr' => ['TR'],
        'zh-CN' => ['CN'],
    ];

    /**
     * The 12 languages the CRM, the client area and the WebTrader offer, in the dropdown's order:
     * code => [English name, flagcdn flag code, native name].
     */
    public const CHOICES = [
        'en' => ['English', 'gb', 'English'],
        'ar' => ['Arabic', 'sa', 'العربية'],
        'de' => ['German', 'de', 'Deutsch'],
        'es' => ['Spanish', 'es', 'Español'],
        'fr' => ['French', 'fr', 'Français'],
        'hi' => ['Hindi', 'in', 'हिन्दी'],
        'it' => ['Italian', 'it', 'Italiano'],
        'nl' => ['Dutch', 'nl', 'Nederlands'],
        'pt' => ['Portuguese', 'pt', 'Português'],
        'ru' => ['Russian', 'ru', 'Русский'],
        'tr' => ['Turkish', 'tr', 'Türkçe'],
        'zh-CN' => ['Chinese', 'cn', '简体中文'],
    ];

    /** The choices as the client API lists them. */
    public static function options(): array
    {
        return collect(self::CHOICES)->map(fn ($c, $code) => ['code' => $code, 'label' => $c[0], 'native' => $c[2], 'flag' => $c[1]])->values()->all();
    }

    public static function for(?string $countryCode): ?string
    {
        $code = strtoupper((string) $countryCode);
        foreach (self::LANGUAGES as $language => $countries) {
            if (in_array($code, $countries, true)) {
                return $language;
            }
        }

        return null;
    }
}
