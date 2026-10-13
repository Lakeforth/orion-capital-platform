<?php

namespace App\Support\LeadMax;

/**
 * The brand's main website, which the logo in the client area and the WebTrader opens
 * (GET /api/v1/settings "website_url"). Always an absolute http(s) URL, or null.
 */
class BrandWebsite
{
    /**
     * One value, edited from two screens: the superadmin's tenant settings › Modules › Brand
     * Website and CRM-Max Settings › General › Website both save brand_website_url. Before it,
     * a CRM-Max Website saved under leadmax_general, then the classic site_url.
     */
    public static function url(): ?string
    {
        $general = company_settings('leadmax_general', []);

        return self::normalize(company_settings('brand_website_url'))
            ?? self::normalize(is_array($general) ? ($general['website'] ?? null) : null)
            ?? self::normalize(company_settings('site_url'));
    }

    /**
     * "orion-capital.inc" becomes "https://orion-capital.inc"; an empty value, any scheme other
     * than http(s) (javascript:, data:, mailto:...) or a URL without a host becomes null.
     */
    public static function normalize(mixed $value): ?string
    {
        $url = is_scalar($value) ? trim((string) $value) : '';
        if ($url === '') {
            return null;
        }
        if (! str_contains($url, '://')) {
            // A scheme with no "//" (javascript:, mailto:) is refused; "host:8080" is a port, not a scheme.
            if (preg_match('~^[a-z][a-z0-9+.\-]*:(?!\d)~i', $url)) {
                return null;
            }
            $url = 'https://'.ltrim($url, '/');
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && filled($host) && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
