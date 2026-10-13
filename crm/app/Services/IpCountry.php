<?php

namespace App\Services;

use MaxMind\Db\Reader;

/**
 * Country of an IP address, read from a local DB-IP Lite country database
 * (IP Geolocation by DB-IP, https://db-ip.com, CC BY 4.0).
 *
 * The file is downloaded monthly by `leadmax:geoip-update`, so a lookup never calls an outside
 * service. Approximate by nature: a VPN or proxy shows its own country. Private and reserved
 * addresses, a missing database and an unreadable file all answer null -- shown as Unknown,
 * never guessed.
 */
class IpCountry
{
    public const SOURCE = 'DB-IP Lite';

    private ?Reader $reader = null;

    private bool $opened = false;

    /** @var array<string, array{code: string, name: string}|null> */
    private array $found = [];

    public static function path(): string
    {
        return config('services.geoip.database') ?: storage_path('app/geoip/dbip-country-lite.mmdb');
    }

    /** @return array{code: string, name: string}|null */
    public function lookup(?string $ip): ?array
    {
        $ip = trim((string) $ip);
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
            return null;
        }
        if (array_key_exists($ip, $this->found)) {
            return $this->found[$ip];
        }

        try {
            $record = $this->reader()?->get($ip);
        } catch (\Throwable) {
            $record = null;
        }
        $code = strtoupper((string) ($record['country']['iso_code'] ?? ''));

        return $this->found[$ip] = preg_match('/^[A-Z]{2}$/', $code) && $code !== 'ZZ'
            ? ['code' => $code, 'name' => (string) ($record['country']['names']['en'] ?? $code)]
            : null;
    }

    /** ActivityLog info for the request's IP: the country, its source and when it was looked up. */
    public function context(?string $ip): array
    {
        $country = $this->lookup($ip);

        return $country ? [
            'country' => $country['name'],
            'country_code' => $country['code'],
            'country_source' => self::SOURCE,
            'country_at' => now()->toIso8601String(),
        ] : [];
    }

    private function reader(): ?Reader
    {
        if (! $this->opened) {
            $this->opened = true;
            try {
                $this->reader = is_file(self::path()) ? new Reader(self::path()) : null;
            } catch (\Throwable) {
                $this->reader = null;
            }
        }

        return $this->reader;
    }
}
