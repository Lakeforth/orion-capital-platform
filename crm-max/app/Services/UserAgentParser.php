<?php

namespace App\Services;

/**
 * Tiny best-effort User-Agent parser -- enough to label sessions/login
 * history with a "Chrome on macOS" style string without adding a package.
 */
class UserAgentParser
{
    public static function parse(?string $ua): array
    {
        $ua = $ua ?? '';

        return [
            'browser' => self::browser($ua),
            'platform' => self::platform($ua),
            'device_type' => self::deviceType($ua),
        ];
    }

    /** "Chrome on macOS" style label. */
    public static function label(?string $ua): string
    {
        $parts = self::parse($ua);
        $browser = $parts['browser'] ?: 'Unknown browser';
        $platform = $parts['platform'];

        return $platform ? "{$browser} on {$platform}" : $browser;
    }

    private static function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Safari') => 'Safari',
            default => '',
        };
    }

    private static function platform(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') || str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => '',
        };
    }

    private static function deviceType(string $ua): string
    {
        if (str_contains($ua, 'iPhone') || (str_contains($ua, 'Android') && str_contains($ua, 'Mobile'))) {
            return 'mobile';
        }
        if (str_contains($ua, 'iPad') || str_contains($ua, 'Tablet')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
