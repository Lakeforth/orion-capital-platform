<?php

namespace App\Support\LeadMax;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * LeadMax feature switches on Settings > Modules (super admin only). Unlike module_enabled(), these default
 * to ON: a tenant keeps every page until the super admin turns one off (e.g. while a payment is pending).
 * EnsureLeadMaxFeature blocks the routes of a switched-off feature; the layout hides its menu entries.
 */
class Features
{
    /** key => [label shown on the Modules page, help text] */
    public const SWITCHES = [
        'leadmax_finances' => ['LeadMax: Finances', 'The Finances page (transactions, fees, payouts, wallets, reports).'],
        'leadmax_calendar' => ['LeadMax: Calendar', 'The reminders calendar.'],
        'leadmax_lead_splitter' => ['LeadMax: Lead Splitter', 'Lead distribution rules.'],
        'leadmax_upload_leads' => ['LeadMax: Upload Leads', 'CSV lead import, including the Import button on the Leads page.'],
        'leadmax_assets' => ['LeadMax: Assets', 'The instruments list.'],
        'leadmax_markups' => ['LeadMax: Markups', 'Spread markups and leverage per category.'],
        'leadmax_all_reports' => ['LeadMax: All reports', 'Off: only the Conversion by GEO report is available.'],
        'leadmax_all_settings' => ['LeadMax: All settings tabs', 'Off: only the General settings tab is available.'],
        'leadmax_team_chat' => ['LeadMax: Team chat', 'Chat between staff (admins, managers, agents). Client chat is not affected.'],
    ];

    /** Route name (after "admin.leadmax.") patterns owned by each switch. */
    private const ROUTES = [
        'leadmax_finances' => ['finances', 'finances.*'],
        'leadmax_calendar' => ['calendar'],
        'leadmax_lead_splitter' => ['lead-splitter', 'lead-splitter.*'],
        'leadmax_upload_leads' => ['upload-leads', 'upload-leads.*'],
        'leadmax_assets' => ['assets'],
        'leadmax_markups' => ['markups', 'markups.*'],
        'leadmax_all_settings' => ['settings.currency', 'settings.currency.*'],
        'leadmax_team_chat' => ['chat.team'],
    ];

    /** The one report that stays available with "All reports" off. */
    public const BASE_REPORT = 'conversion-by-geo';

    /**
     * Pages withheld from CRM-Max entirely -- see config/leadmax.php. A withheld page leaves the
     * sidebar rather than sitting there greyed out, and its routes stop answering.
     *
     * @return array<string, array<int, string>> nav key => route name patterns
     */
    public static function hiddenPages(): array
    {
        return (array) config('leadmax.hidden_pages', []);
    }

    /** Is this sidebar entry withheld? */
    public static function hiddenPage(string $navKey): bool
    {
        return array_key_exists($navKey, self::hiddenPages());
    }

    /** Is this request for a withheld page? */
    public static function hiddenRequest(Request $request): bool
    {
        return self::hiddenRoute((string) $request->route()?->getName());
    }

    public static function hiddenRoute(string $routeName): bool
    {
        $name = Str::after($routeName, 'leadmax.');
        foreach (self::hiddenPages() as $patterns) {
            if (Str::is($patterns, $name)) {
                return true;
            }
        }

        return false;
    }

    public static function enabled(string $key): bool
    {
        $value = company_settings($key, 'yes');

        return is_bool($value) ? $value : in_array($value, [1, '1', 'true', 'yes', 'on'], true);
    }

    public static function reportEnabled(string $slug): bool
    {
        return $slug === self::BASE_REPORT || self::enabled('leadmax_all_reports');
    }

    /** The switch that owns this request, or null when no switch applies. */
    public static function forRequest(Request $request): ?string
    {
        $name = Str::after((string) $request->route()?->getName(), 'leadmax.');
        foreach (self::ROUTES as $key => $patterns) {
            if (Str::is($patterns, $name)) {
                return $key;
            }
        }
        if (Str::is(['reports.show', 'reports.export'], $name) && $request->route('report') !== self::BASE_REPORT) {
            return 'leadmax_all_reports';
        }
        if (Str::is(['chat.send', 'chat.pin'], $name) && $request->route('type') === 'team') {
            return 'leadmax_team_chat';
        }
        if ($name === 'settings' && $request->filled('tab') && $request->input('tab') !== 'general') {
            return 'leadmax_all_settings';
        }

        return null;
    }

    /** Is the feature behind this LeadMax route name available? (for menus and links) */
    public static function routeEnabled(string $routeName): bool
    {
        if (self::hiddenRoute($routeName)) {
            return false;
        }
        $name = Str::after($routeName, 'leadmax.');
        foreach (self::ROUTES as $key => $patterns) {
            if (Str::is($patterns, $name)) {
                return self::enabled($key);
            }
        }

        return true;
    }
}
