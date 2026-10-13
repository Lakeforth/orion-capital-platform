<?php

namespace App\Support\LeadMax;

/**
 * Splits a stored phone number into its dial code and the rest, so the UI can show
 * "+972-505043163" while still copying and dialling the plain "+972505043163".
 *
 * Dial codes come from public/config/countries.json (the same file countryOptions() falls back to;
 * the countries table is empty). No phone parsing library is available here, so this only splits on
 * a dial code the number actually starts with -- it never reformats or validates the rest.
 */
class Phone
{
    /** @var array<string,string>|null ISO alpha-2 (lowercase) => dial code */
    private static ?array $codes = null;

    /** The usual country for a dial code several countries share (countries.json lists e.g. Canada before the US). */
    private const MAIN = ['+1' => 'us', '+44' => 'gb', '+358' => 'fi', '+262' => 're', '+595' => 'py', '+61' => 'au', '+47' => 'no'];

    /** @return array<string,string> */
    public static function codes(): array
    {
        if (self::$codes === null) {
            self::$codes = [];
            $path = public_path('config/countries.json');
            $rows = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
            foreach ($rows ?? [] as $row) {
                if (! empty($row['code']) && ! empty($row['dial_code'])) {
                    self::$codes[mb_strtolower($row['code'])] = $row['dial_code'];
                }
            }
        }

        return self::$codes;
    }

    /** The dial code for a country name, resolved through Geo so aliases and ISO codes both work. */
    public static function dial(?string $country): ?string
    {
        $code = Geo::find($country)['code'];

        return $code ? (self::codes()[$code] ?? null) : null;
    }

    /**
     * @return array{raw: string, dial: ?string, national: ?string, display: string, iso: ?string}
     *               raw is what to copy and dial; display is what to show; iso is the country the
     *               number's dial code belongs to (its flag), which need not be the lead's country.
     */
    public static function parts(?string $phone, ?string $country = null): array
    {
        $raw = trim((string) $phone);
        if ($raw === '') {
            return ['raw' => '', 'dial' => null, 'national' => null, 'display' => '', 'iso' => null];
        }
        $compact = (str_starts_with($raw, '+') ? '+' : '').preg_replace('/\D/', '', $raw);
        // "00" is the international prefix written out: 00972... is +972...
        if (str_starts_with($compact, '00') && trim($compact, '0') !== '') {
            $compact = '+'.substr($compact, 2);
        }

        // The lead's own country first; it is the one we can actually trust.
        $dial = self::dial($country);
        // Every number gets its country code (user, 10 Oct). One stored without "+" names no country
        // itself, so only then is it completed from the lead's: 972... is read as +972..., a local
        // 0501... drops its trunk 0. Without a lead country, digits that start with a known code are
        // read as international. Placeholder zeros stay as they are.
        if (! str_starts_with($compact, '+') && trim($compact, '0') !== '') {
            $digits = $dial !== null ? ltrim($dial, '+') : null;
            if ($digits !== null) {
                $compact = str_starts_with($compact, $digits) && strlen($compact) > strlen($digits) + 6
                    ? '+'.$compact
                    : $dial.(str_starts_with($compact, '0') ? substr($compact, 1) : $compact);
            } elseif (collect(self::codes())->contains(fn ($code) => self::splits('+'.$compact, $code))) {
                $compact = '+'.$compact;
            }
        }
        if ($dial !== null && self::splits($compact, $dial)) {
            // A shared code (+1) is credited to the lead's country when it is one of its owners.
            return self::make($compact, $dial, Geo::find($country)['code']);
        }
        // Otherwise the longest dial code this number starts with, so +1 never wins over +1242.
        $best = null;
        foreach (array_unique(self::codes()) as $candidate) {
            if (self::splits($compact, $candidate) && ($best === null || strlen($candidate) > strlen($best))) {
                $best = $candidate;
            }
        }

        return $best === null
            ? ['raw' => $compact, 'dial' => null, 'national' => null, 'display' => $compact, 'iso' => null]
            : self::make($compact, $best, self::MAIN[$best] ?? array_search($best, self::codes(), true) ?: null);
    }

    /** Only split when something is left over, so a bare dial code stays whole. */
    private static function splits(string $compact, string $dial): bool
    {
        return str_starts_with($compact, $dial) && strlen($compact) > strlen($dial);
    }

    /** @return array{raw: string, dial: string, national: string, display: string, iso: ?string} */
    private static function make(string $compact, string $dial, ?string $iso): array
    {
        $national = substr($compact, strlen($dial));

        return ['raw' => $compact, 'dial' => $dial, 'national' => $national, 'display' => $dial.'-'.$national, 'iso' => $iso];
    }
}
