<?php

use App\Models\Asset;
use App\Models\Category;
use App\Models\Company;
use App\Models\Service;
use App\Models\Token;
use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\Splade\Facades\Toast;
use ProtoneMedia\Splade\FileUploads\ExistingFile;

function format_price($price): string
{
    return currency().number_format(\App\Support\LeadMax\DisplayCurrency::convert((float) $price));
}

/** A currency amount with cents -- for balances, where format_price()'s whole-unit rounding hides small trade P/L. */
function format_money($price): string
{
    return currency().number_format(\App\Support\LeadMax\DisplayCurrency::convert((float) $price), 2);
}

/**
 * A trade/instrument price, not a currency amount -- no currency symbol,
 * precision driven by the instrument itself (Asset::displayPrecision())
 * instead of a flat 2dp, so a GBP/USD price like 1.26845 isn't rounded down
 * to 1.27 and the entry/exit prices an admin sees actually match what the
 * customer traded at.
 */
function format_trade_price($price, ?Asset $asset = null): string
{
    $precision = $asset?->displayPrecision() ?? 5;

    return number_format((float) $price, $precision);
}

function format_amount($price): string
{
    return (float) rtrim(rtrim(sprintf('%.4f', $price), '0'), '.');
}

function company_settings($key = null, $default = null)
{
    if (! app()->bound('company')) {
        return $default;
    }

    $company = app('company');

    if (! $company) {
        return $default;
    }

    // The settings column is cast to an array, and Eloquent decodes that JSON again on every
    // read. Callers that ask per row (the asset list asks ~25 settings per instrument) decoded
    // a tenant's whole settings blob thousands of times per request, so the last decode is
    // reused for as long as the stored JSON is unchanged.
    static $decoded = null;
    $raw = $company->getAttributes()['settings'] ?? null;

    if (is_string($raw) && $decoded !== null && $decoded[0] === $raw) {
        $settings = $decoded[1];
    } else {
        $settings = $company->settings;

        // If string JSON, convert to array
        if (is_string($settings)) {
            $settings = json_decode($settings, true) ?? [];
        } elseif (! is_array($settings)) {
            $settings = [];
        }

        if (is_string($raw)) {
            $decoded = [$raw, $settings];
        }
    }

    // If no key provided, return all settings
    if ($key === null) {
        return $settings;
    }

    return $settings[$key] ?? $default;
}

function module_enabled(string $module, string $default = 'no'): bool
{
    // $default matters for a module that is already on everywhere: introducing
    // the switch must not turn the feature off for every tenant that has never
    // seen the setting.
    $value = company_settings($module, $default);

    if (is_bool($value)) {
        return $value;
    }

    return in_array($value, [1, '1', 'true', 'yes', 'on'], true);
}

function recovery_module_bypassed(): bool
{
    return app()->environment('local')
        || filter_var(env('RECOVERY_MODULE_BYPASS', false), FILTER_VALIDATE_BOOLEAN);
}

function can_delete_action(): bool
{
    if (\App\Services\RestrictedManagerAccess::active()) {
        return false;
    }

    return is_admin() || module_enabled('allow_non_admin_delete_action');
}

if (! function_exists('company_subscription_expired')) {
    function company_subscription_expired(?\App\Models\Company $company = null): bool
    {
        $company = $company ?? (app()->bound('company') ? app('company') : null);
        if (! $company) {
            return false;
        }

        if (! $company->enable_subscription) {
            return false;
        }

        // Treat today as valid; expired only if the date is before today. No end date is an
        // open-ended subscription (Valid Till is optional; a cancelled plan also clears it).
        $validTill = $company->subscription_valid_till; // cast to date
        if (is_null($validTill)) {
            return false;
        }

        return $validTill->lt(Carbon::today());
    }
}

function set_company_setting($key, $value)
{
    $company = app('company');

    if (! $company) {
        return false;
    }

    $settings = company_settings();
    $settings[$key] = $value;

    $company->settings = $settings;

    return $company->save();
}

function settings_sub_menus(): array
{
    $allMenus = [
        [
            'title' => 'Site Settings',
            'route' => 'admin.settings.index',
            'active' => true,
        ],
        [
            'title' => 'Modules',
            'route' => 'admin.settings.modules',
            'active' => is_super_admin(),
        ],
        [
            'title' => 'Demo',
            'route' => 'admin.settings.demo',
            'active' => is_super_admin() && company_settings('is_demo', 'no') === 'yes',
        ],
        [
            'title' => 'SMTP',
            'route' => 'admin.settings.smtp',
            'active' => is_super_admin(),
        ],
        [
            'title' => 'Email Settings',
            'route' => 'admin.settings.email',
            'active' => true,
        ],
        [
            'title' => 'Trade Settings',
            'route' => 'admin.settings.trade',
            'active' => true,
        ],
        [
            'title' => 'Deposit Settings',
            'route' => 'admin.settings.deposit',
            'active' => true,
        ],
        [
            'title' => 'Deposit Wallets',
            'route' => 'admin.settings.wallets',
            'active' => true,
        ],

        [
            'title' => 'Assets',
            'route' => 'admin.assets.listing',
            'active' => true,
        ],

        [
            'title' => 'Permissions',
            'route' => 'admin.settings.roles',
            'active' => true,
        ],

        [
            'title' => 'Page',
            'route' => 'admin.settings.page',
            'active' => is_super_admin(),
        ],
        [
            'title' => 'Telegram',
            'route' => 'admin.settings.telegram',
            'active' => is_super_admin(),
        ],

    ];

    return array_filter($allMenus, function ($menuItem) {
        return $menuItem['active'] === true;
    });
}

function customer_sub_menus(): array
{
    if (\App\Services\RestrictedManagerAccess::active()) {
        return [['title' => 'Customers', 'route' => 'admin.users.customers', 'active' => true]];
    }

    if (isMini()) {
        $allMenus = [
            [
                'title' => 'All Customers',
                'route' => 'admin.users.customers',
                'active' => true,
            ],
            [
                'title' => 'KYC Verification',
                'route' => 'admin.users.ids',
                'active' => true,
            ],
            [
                'title' => 'Plans',
                'route' => 'admin.plans.index',
                // Brokerage account plans (Basic/Pro/VIP) don't apply to Prop
                // Firm -- challenge packages are a separate concept, managed
                // under the Prop Firm sidebar item instead.
                'active' => is_admin() && ! module_enabled('propfirm_enabled'),
            ],
            [
                'title' => 'Deposits',
                'route' => 'admin.all_deposits',
                'active' => true,
            ],
            [
                'title' => module_enabled('propfirm_enabled') ? 'Payout' : 'Withdrawal',
                'route' => module_enabled('propfirm_enabled') ? 'admin.propfirm.payouts.index' : 'admin.all_withdrawals',
                // While already viewing a specific customer, Payout should
                // jump to that customer's own Payouts tab (loads inline on
                // this same page) instead of leaving to the tenant-wide
                // admin.propfirm.payouts.index page. Brokerage's Withdrawal
                // item is unchanged.
                'url' => (module_enabled('propfirm_enabled') && request()->routeIs('admin.users.show') && request()->route('id'))
                    ? route('admin.users.show', ['id' => request()->route('id'), 'tab' => 'payouts'])
                    : null,
                'active' => true,
            ],
            [
                'title' => 'Activities',
                'route' => 'admin.user.logs',
                'active' => is_admin(),
            ],
            [
                'title' => 'Messages',
                'route' => 'admin.chat.index',
                'active' => true,
            ],
            [
                'title' => 'AI Trading',
                'route' => 'admin.users.customers',
                'url' => route('admin.users.customers', ['tab' => 'ai_trading']),
                'tab' => 'ai_trading',
                'active' => true,
            ],
            [
                'title' => 'AI Recommendations',
                'route' => 'admin.users.customers',
                'url' => route('admin.users.customers', ['tab' => 'ai_recommendations']),
                'tab' => 'ai_recommendations',
                'active' => module_enabled('enable_ai_recommendations'),
            ],
        ];

        return array_filter($allMenus, function ($menuItem) {
            return $menuItem['active'] === true;
        });
    }

    $allMenus = [
        [
            'title' => 'All Customers',
            'route' => 'admin.users.customers',
            'active' => true,
        ],
        [
            'title' => 'KYC Verification',
            'route' => 'admin.users.ids',
            'active' => true,
        ],
        [
            'title' => 'Deposits',
            'route' => 'admin.all_deposits',
            'active' => true,
        ],
        [
            'title' => module_enabled('propfirm_enabled') ? 'Payout' : 'Withdrawals',
            'route' => module_enabled('propfirm_enabled') ? 'admin.propfirm.payouts.index' : 'admin.all_withdrawals',
            'active' => true,
        ],
        [
            'title' => 'AI Trading',
            'route' => 'admin.users.customers',
            'url' => route('admin.users.customers', ['tab' => 'ai_trading']),
            'tab' => 'ai_trading',
            'active' => true,
        ],
        [
            'title' => 'AI Recommendations',
            'route' => 'admin.users.customers',
            'url' => route('admin.users.customers', ['tab' => 'ai_recommendations']),
            'tab' => 'ai_recommendations',
            'active' => module_enabled('enable_ai_recommendations'),
        ],
        [
            'title' => 'Plans',
            'route' => 'admin.plans.index',
            'active' => is_admin(),
        ],

        [
            'title' => 'Plan Features',
            'route' => 'admin.data.plans',
            'active' => is_admin(),
        ],

    ];

    return array_filter($allMenus, function ($menuItem) {
        return $menuItem['active'] === true;
    });
}

function demo_platform_data(?Company $company = null): array
{
    return app(\App\Services\DemoPlatformService::class)->settings($company);
}

function setup_sub_menus(): array
{
    return [
        [
            'title' => 'Labels',
            'route' => 'admin.data.labels',
        ],
        [
            'title' => 'Sources',
            'route' => 'admin.data.sources',
        ],
        [
            'title' => 'Brands',
            'route' => 'admin.data.brands',
        ],

        [
            'title' => 'Status',
            'route' => 'admin.data.status',
        ],

        [
            'title' => 'Campaigns',
            'route' => 'admin.data.campaigns',
        ],

    ];
}

function format_number($price): float
{
    return round($price * 100) / 100;
}

if (! function_exists('asset_contract_size')) {
    /**
     * Per-category contract size (units per 1.0 lot), tenant-configurable.
     * Single source of truth for this lookup -- AssetResource (what a lot is
     * worth when opening a trade) and TradeResource (converting a stored
     * trade's raw volume back to lots for display) must both resolve the
     * exact same value for the same asset, or the volume a client typed
     * when opening a trade won't match what they see afterward.
     *
     * @param  \App\Models\Asset  $asset
     */
    function asset_contract_size($asset): float
    {
        $category = $asset->type === 'crypto' ? 'cryptos' : $asset->type;

        $contract = [
            'cryptos' => company_settings('cryptos_contract', 1),
            'stocks' => company_settings('stocks_contract', 1000),
            'indices' => company_settings('indices_contract', 1000),
            'commodities' => company_settings('commodities_contract', 100),
            'metals' => company_settings('metals_contract', 100),
            'forex' => company_settings('forex_contract', 100000),
        ];

        return (float) ($contract[$category] ?? 1);
    }
}

if (! function_exists('is_active')) {
    function is_active($route, $array = false): string
    {
        if ($array) {
            foreach ($route as $i) {
                if (request()->routeIs($i['active'])) {
                    return true;
                }
            }

            return false;
        } else {
            return request()->routeIs($route);
        }
    }
}

function isMini(): bool
{
    return config('app.is_mini');
}

function hasPlan(): bool
{
    return true;
}

function customer_plan_feature_icon_catalog(): array
{
    return [
        'deposit' => ['label' => 'Deposit', 'svg' => 'heroicon-o-arrow-trending-up'],
        'bonus' => ['label' => 'Bonus', 'svg' => 'heroicon-o-arrows-right-left'],
        'training' => ['label' => 'Training', 'svg' => 'heroicon-o-calendar-days'],
        'insured' => ['label' => 'Insured', 'svg' => 'heroicon-o-lock-closed'],
        'leverage' => ['label' => 'Leverage', 'svg' => 'heroicon-o-exclamation-triangle'],
        'reward' => ['label' => 'Reward', 'svg' => 'heroicon-o-chevron-right'],
        'vip' => ['label' => 'VIP', 'svg' => 'heroicon-o-chevron-right'],
        'sparkles' => ['label' => 'Sparkles', 'svg' => 'heroicon-o-sparkles'],
        'shield' => ['label' => 'Shield', 'svg' => 'heroicon-o-shield-check'],
        'chart' => ['label' => 'Chart', 'svg' => 'heroicon-o-chart-bar'],
        'signal' => ['label' => 'Signal', 'svg' => 'heroicon-o-signal'],
        'wallet' => ['label' => 'Wallet', 'svg' => 'heroicon-o-wallet'],
        'card' => ['label' => 'Card', 'svg' => 'heroicon-o-credit-card'],
        'support' => ['label' => 'Support', 'svg' => 'heroicon-o-lifebuoy'],
        'cloud' => ['label' => 'Cloud', 'svg' => 'heroicon-o-cloud'],
        'launch' => ['label' => 'Launch', 'svg' => 'heroicon-o-rocket-launch'],
        'briefcase' => ['label' => 'Briefcase', 'svg' => 'heroicon-o-briefcase'],
    ];
}

function customer_plan_feature_icon_options(): array
{
    return collect(customer_plan_feature_icon_catalog())
        ->mapWithKeys(fn (array $icon, string $key) => [$key => $icon['label']])
        ->toArray();
}

function customer_plan_feature_icon_svg(?string $iconKey): string
{
    return customer_plan_feature_icon_catalog()[$iconKey]['svg'] ?? customer_plan_feature_icon_catalog()['sparkles']['svg'];
}

function customer_plan_feature_default_icon_key(?string $featureName): string
{
    return match (strtolower(trim((string) $featureName))) {
        'minimum deposit' => 'wallet',
        'welcome bonus' => 'bonus',
        'insured trading' => 'shield',
        'private training' => 'training',
        'leverage' => 'leverage',
        'trading signals' => 'signal',
        'expedite withdrawals' => 'launch',
        'trade nation trade rewards' => 'reward',
        'access to vip room' => 'vip',
        default => 'sparkles',
    };
}

function customer_plan_feature_definitions(bool $activeOnly = true): array
{
    return \App\Models\Data::query()
        ->where('type', 'plan')
        ->orderBy('id')
        ->get(['id', 'name', 'icon_key', 'image', 'use_image', 'is_active'])
        ->when($activeOnly, fn ($features) => $features->filter(fn ($feature) => $feature->is_active))
        ->mapWithKeys(function ($feature, $index) {
            $imageUrl = $feature->use_image && $feature->image
                ? getFullImage($feature->image)
                : null;

            return [$index => [
                'id' => $feature->id,
                'index' => $index,
                'name' => $feature->name,
                'icon_key' => $feature->icon_key,
                'use_image' => (bool) $feature->use_image,
                'image_url' => $imageUrl,
            ]];
        })
        ->toArray();
}

function plan_features(): array
{
    return collect(customer_plan_feature_definitions())
        ->mapWithKeys(fn (array $feature, int $index) => [$index => $feature['name']])
        ->toArray();
}

function plan_feature_values(array $features): array
{
    return collect(plan_features())
        ->map(fn ($title, $index) => $features[$index] ?? null)
        ->values()
        ->toArray();
}

function subscription_plan_features(): array
{
    try {
        return \App\Models\GlobalData::query()
            ->where('type', 'plan_features')
            ->orderBy('name')
            ->get(['name', 'data_type'])
            ->map(fn ($feature) => [
                'title' => $feature->name,
                'type' => $feature->data_type,
            ])
            ->toArray();
    } catch (\Throwable $e) {
        return [];
    }
}

function getPlanBgColor($color)
{
    if (! $color) {
        return 'background-color: #f3f4f6;';
    }

    return "background-color: $color;";
}

if (! function_exists('plan_options')) {
    function plan_options(): array
    {
        try {
            return \App\Models\AccountPlan::select('id', 'title')
                ->orderBy('title')
                ->get()
                ->map(fn ($p) => ['key' => $p->id, 'value' => $p->title])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}

/**
 * The symbol amounts are shown with: whatever the viewer picked in the topbar switcher, or the
 * configured default. See App\Support\LeadMax\DisplayCurrency -- with nothing selected, and on
 * any request without a session, this returns exactly what it always did.
 */
function currency()
{
    return \App\Support\LeadMax\DisplayCurrency::symbol();
}

function svgs()
{
    return [
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                                        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white"  class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                                        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0-3.75-3.75M17.25 21 21 17.25" />
                                        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" />
                                        </svg>',
        ' <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                        </svg>',
        '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>',

    ];
}

function dashboard(): array
{
    $items = [
        [
            'title' => 'Trade Room',
            'info' => 'View and manage your trading activities',
            'link' => route('backend.traderoom'),
            'icon' => svgs()[0],
        ],
        [
            'title' => 'Deposit',
            'info' => 'Add funds to your trading account',
            'link' => route('backend.deposit'),
            'icon' => svgs()[1],
        ],
        [
            'title' => 'Withdraw',
            'info' => 'Withdraw funds from your account',
            'link' => route('backend.withdrawal'),
            'icon' => svgs()[2],
        ],
        [
            'title' => 'Transactions',
            'info' => 'View your transaction records',
            'link' => route('backend.transactions'),
            'icon' => svgs()[3],
        ],
        [
            'title' => 'Order Book',
            'info' => 'View current market orders and trades',
            'link' => route('backend.trades'),
            'icon' => svgs()[4],
        ],
        [
            'title' => 'KYC',
            'info' => 'Manage your identity verification',
            'link' => route('backend.kyc'),
            'icon' => svgs()[5],
        ],
        //        [
        //            'title' => 'Security Settings',
        //            'info' => 'Configure your account security preferences',
        //            'link' => "#",
        //            'icon' => svgs()[6],
        //        ],
        //        [
        //            'title' => 'Smart Contract',
        //            'info' => 'View accounts with no activity',
        //            'link' => route('investment.dashboard'),
        //            'icon' => svgs()[7],
        //        ],
        //        [
        //            'title' => '401k Investment',
        //            'info' => 'Manage your portfolio and ECN investments',
        //            'link' => route('portfolio.dashboard'),
        //            'icon' => svgs()[7],
        //        ]
    ];

    if (has_smart_contract()) {
        $items[] = [
            'title' => 'Smart Contract',
            'info' => 'View accounts with no activity',
            'link' => route('investment.dashboard'),
            'icon' => svgs()[7],
        ];
    }
    if (has_ecn()) {
        $items[] = [
            'title' => '401k Investment',
            'info' => 'Manage your portfolio and ECN investments',
            'link' => route('portfolio.dashboard'),
            'icon' => svgs()[7],
        ];
    }
    //    if (has_accounts()){
    //        $items[] =  [
    //            'title' => 'Accounts',
    //            'info' => 'Manage your account',
    //            'link' => route('backend.account'),
    //            'icon' => svgs()[1],
    //        ];
    //    }

    return $items;
}

//CART

function convertToArray($string): array
{
    return explode(',', $string);
}

function getSortingItem(): array
{
    return [
        'popular' => 'Most Popular',
        'latest' => 'Latest First',
        'oldest' => 'Oldest First',
        'higher_price' => 'Higher First',
        'lower_price' => 'Lower First',
    ];
}

function getFeaturedCategories()
{
    $categoryService = new \App\Services\CategoryService;

    $categories = $categoryService->getFeaturedCategories(2);

    return $categories->pluck('id')->toArray();
}

function getFeaturedPosts()
{
    $blogService = new \App\Services\BlogService;

    return $blogService->getFeaturedPosts(3);

}

function delivery_methods(): array
{
    return [
        [
            'title' => 'Home Delivery',
            'info' => '7 - 10 business days',
            'id' => 'home_delivery',
        ], [
            'title' => 'Pick up location',
            'info' => '7 - 10 business days',
            'id' => 'pickup_location',
        ],
    ];
}
function payment_methods(): array
{
    return [
        [
            'title' => 'Cash on Delivery',
            'info' => 'Pay with cash on delivery',
            'id' => 'cash',
        ],
    ];
}

function getTaxes(): float
{
    return 7.5;
}

function generateUniqueOrderNumber(): string
{
    $y = (int) Carbon::now()->format('y');
    $m = (int) Carbon::now()->format('md');
    $s = (int) Carbon::now()->format('His');
    $u = (int) Carbon::now()->format('u');

    $last = $y + $m + $s + $u + mt_rand(11, 99);

    return $y.'-'.$last;
}

function reportError($message, $title, $user_id, $category, $level = 1): void
{

    Log::error($message);

    $data = [
        'title' => $title,
        'message' => $message,
        'user_id' => $user_id,
        'category' => $category,
    ];

}

function user_has_permission($permission): bool
{
    if (\auth()->user()->hasRole('superadmin')) {
        return true;
    } else {
        return auth()->user()->hasPermission($permission);
    }
}

function is_admin(): bool
{
    if (! auth()->check()) {
        return false;
    }

    return \auth()->user()->hasRole(['admin', 'superadmin', 'tenant_admin']);
}

function is_super_admin(): bool
{
    if (! auth()->check()) {
        return false;
    }

    return \auth()->user()->hasRole(['superadmin']);
}

function is_manager(): bool
{
    if (! auth()->check()) {
        return false;
    }

    return \auth()->user()->hasRole(['manager', 'agent']);
}

function get_site_name()
{
    $fallback = config('app.name') ?: 'Trading';

    if (app()->bound('company') && app('company')) {
        $company = app('company');

        return company_settings('site_name', $company->name ?? $fallback) ?: ($company->name ?? $fallback);
    }

    return $fallback;
}

function can_manager($auth_id, $manager_id, $onboarded_by): bool
{
    if (is_admin()) {
        return true;
    }
    $user_id = \auth()->id();
    if ($user_id == $auth_id ||
        $user_id == $manager_id ||
        $user_id == $onboarded_by) {
        return true;
    }

    return false;
}

function is_vendor(): bool
{
    if (! auth()->check()) {
        return false;
    }

    return \auth()->user()->hasRole(['vendor']);
}

function vendor_is_incomplete_setup(): bool
{
    $vendor = auth()->user()->vendor;
    $message = null;
    if (! $vendor->open_time || ! $vendor->close_time) {
        $message = 'Please set your opening and closing time';
    }
    if (str_contains('images/not_found.png', $vendor->logo)) {
        $message = 'Please set up your logo and banner';
    }
    if ($vendor->services()->count() < 1) {
        $message = 'Please enable at least 1 service';
    }

    if ($message) {
        Toast::warning($message);

        return true;
    }

    return false;
}

function is_admin_staff(): bool
{
    if (! auth()->check()) {
        return false;
    }

    return \auth()->user()->hasRole(['admin', 'superadmin', 'tenant_admin', 'manager', 'marketer']);
}

function is_admin_manager(): bool
{
    if (! auth()->check()) {
        return false;
    }

    return \auth()->user()->hasRole(['admin', 'superadmin', 'tenant_admin', 'manager']);
}

function get_basic_pie($values, $data): array
{
    return [
        'width' => '100%',
        'height' => '300',
        'type' => 'pie',
        'series' => $values,
        'options' => [
            'fontFamily' => 'Satoshi, sans-serif',
            'labels' => $data,
        ],
    ];
}

function has_permission($route): string
{
    if (\App\Services\RestrictedManagerAccess::active()) {
        return \App\Services\RestrictedManagerAccess::allowedAdminRoute($route);
    }

    if (str_starts_with($route, 'admin.leadmax.') && module_enabled('leadmax_enabled')) {
        return ! \auth()->user()->hasRole(['customer', 'user']);
    }

    if (\auth()->user()->hasRole(['superadmin'])) {
        return true;
    }

    if (\auth()->user()->hasRole(['admin']) && module_enabled('propfirm_enabled') && propfirm_admin_gated_route($route)) {
        return true;
    }

    $permission = str_replace('destroy', 'delete', str_replace('index', 'read', str_replace('admin-', '', str_replace('.', '-', $route))));

    return Auth::user()->hasPermission($permission);
}

/**
 * Routes the 'admin' role only gets on a per-tenant basis, once that tenant
 * turns the Prop Firm module on -- other tenants using this same shared admin
 * CRM (custom.your-platform-host.example serves ~20 brokerages) see no change in behavior.
 */
function propfirm_admin_gated_route(string $route): bool
{
    $prefixes = ['admin.campaigns.', 'admin.welcome-messages.', 'admin.propfirm.'];

    foreach ($prefixes as $prefix) {
        if (str_starts_with($route, $prefix)) {
            return true;
        }
    }

    return false;
}

function get_route($route)
{
    if (strlen($route) > 2) {
        return route($route);
    }

    return '#';
}

function get_existing_file($url): ExistingFile|array|null
{
    if (default_image($url)) {
        return null;
    }

    return Storage::disk('public')->exists(get_file_name($url)) ?
        ExistingFile::fromDisk('public')->get(get_file_name($url)) : null;
}

function default_image($url): bool
{
    if ($url == asset('images/not_found.png')) {
        return true;
    } else {
        return false;
    }
}

function get_file_name($url): string
{
    return str_replace(asset('/storage'), '', $url);
}

function get_agents()
{
    return [
        '1' => 'Agent 1',
    ];
}

function get_active_status()
{
    return [
        '1' => 'Active',
        '0' => 'In Active',
    ];
}

function get_teams()
{
    return Cache::flexible('get_teams', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'team')
            ->pluck('name')
            ->toArray();
    });
}

function log_activity($title, $info, $user = null, $type = null, $sendMail = false): void
{

    $data = [
        'title' => $title,
        'info' => $info,
    ];
    if (! $user && \auth()->user()) {
        $data['user_id'] = \auth()->id();
    } else {
        $data['user_id'] = $user;
    }

    $data['type'] = $type ?? 'log';

    \App\Models\ActivityLog::create($data);

}

function getDomainStatus($status): string
{
    if ($status == 'failed') {
        return 'bg-red-600';
    }
    if ($status == 'pending') {
        return 'bg-yellow-600';
    }
    if ($status == 'ssl_initiated') {
        return 'bg-blue-600';
    }
    if ($status == 'activating_ssl') {
        return 'bg-orange-500';
    }
    if ($status == 'active') {
        return 'bg-green-600';
    }

    return 'bg-yellow-600';
}

function slugToText($slug): string
{
    $text = str_replace(['-', '_'], ' ', $slug);

    // Capitalize each word
    $text = ucwords($text);

    return $text;
}

/**
 * A prior fix here queried the domains table for anything matching
 * `LIKE 'client-area%'`, assuming every tenant names their real
 * client-area domain that way. Most don't -- `front_end_url` (already
 * admin-configured per company, already used for this exact purpose by
 * AiRecommendationService's dashboard CTA and elsewhere) is the actual
 * source of truth and needs no naming convention at all. Confirmed via a
 * full company sweep: at least 10 active tenants (Trivon Funded, Apex
 * Funded, Equity Markets Pro, Axion Capital, Fin Capital Markets,
 * Quantbridge Finance, Trade Algo, Bitrace, and others) use `secure.*` or
 * other prefixes and were silently falling back to the generic computed
 * `client-area-{slug}.{platform domain}` URL -- a different, logged-out
 * origin the client never actually uses. The domains-table lookup is kept
 * as a second-tier fallback for the handful of tenants that DO happen to
 * have a `client-area%`-prefixed domain registered but no `front_end_url`
 * set.
 */
function company_dashboard_url(Company $company): string
{
    $frontEndUrl = $company->settings['front_end_url'] ?? null;

    if ($frontEndUrl) {
        return rtrim($frontEndUrl, '/');
    }

    $customDomain = \App\Models\Domain::query()
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->where('domain', 'like', 'client-area%')
        ->value('domain');

    if ($customDomain) {
        return 'https://'.$customDomain;
    }

    return $company->urls['client_area']['url'] ?? $company->url;
}

function company_admin_url(Company $company): string
{
    return $company->urls['crm_backoffice']['url'] ?? rtrim($company->url, '/').'/panel';
}

function company_webtrader_url(Company $company): string
{
    $configuredUrl = $company->setting('webtrader_url');

    if (filled($configuredUrl)) {
        return rtrim((string) $configuredUrl, '/');
    }

    $customDomain = \App\Models\Domain::query()
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->where('domain', 'like', 'webtrader%')
        ->value('domain');

    if ($customDomain) {
        return 'https://'.$customDomain;
    }

    return $company->urls['webtrader']['url'] ?? str_replace('://', '://webtrader_'.$company->slug.'.', config('app.url'));
}

/**
 * Mirrors the frontend's isWebtraderHost() (src/lib/webtrader-host.ts) --
 * keep the two in sync if either changes. Used to short-circuit
 * platform-wide Laravel routes (like GET /login) that would otherwise
 * intercept a webtrader hostname's request before it ever reaches the
 * React SPA fallback.
 */
function is_webtrader_host(): bool
{
    $firstLabel = explode('.', request()->getHost())[0] ?? '';

    return $firstLabel === 'webtrader'
        || str_starts_with($firstLabel, 'webtrader-')
        || str_starts_with($firstLabel, 'webtrader_');
}

function get_desks()
{
    return Cache::flexible('get_desks', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'desk')
            ->pluck('name')
            ->toArray();
    });
}

function getVerificationStatus()
{
    return [
        'approved' => 'Approved',
        'pending' => 'Pending',
        'rejected' => 'Rejected',
        'completed' => 'Completed',
        'incomplete' => 'In Complete',
    ];
}

function getAssetCategories() {}

function get_labels()
{
    return Cache::flexible('get_labels', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'label')
            ->pluck('name')
            ->toArray();
    });
}

function get_ecn_assets()
{
    return Cache::flexible('get_ecn_assets', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'ecn')
            ->pluck('name')
            ->toArray();
    });
}

function get_ecn_assets_filter()
{
    return Cache::flexible('get_ecn_assets_filters', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'ecn')
            ->pluck('name', 'name')
            ->toArray();
    });
}

function get_campaigns()
{
    return Cache::flexible('get_campaigns', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'campaign')
            ->pluck('name')
            ->toArray();
    });
}

function get_lead_status()
{
    return Cache::flexible('get_status', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'status')
            ->pluck('name')
            ->toArray();
    });
}

function get_sources()
{
    return Cache::flexible('get_source', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'source')
            ->pluck('name')
            ->toArray();
    });
}

function get_origins()
{
    return Cache::flexible('get_origins', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'origin')
            ->pluck('name')
            ->toArray();
    });
}

function get_brands()
{
    return Cache::flexible('get_brands', [30, 60], function () {
        return DB::table('data')
            ->where('type', 'label')
            ->pluck('name')
            ->toArray();
    });
}

function get_currencies(): array
{
    return [
        'AED' => 'United Arab Emirates Dirham',
        'AUD' => 'Australian Dollar',
        'BGN' => 'Bulgarian Lev',
        'BRL' => 'Brazilian Real',
        'CAD' => 'Canadian Dollar',
        'CHF' => 'Swiss Franc',
        'CNY' => 'Chinese Yuan',
        'CZK' => 'Czech Koruna',
        'DKK' => 'Danish Krone',
        'EUR' => 'Euro',
        'GBP' => 'British Pound Sterling',
        'HKD' => 'Hong Kong Dollar',
        'HRK' => 'Croatian Kuna',
        'HUF' => 'Hungarian Forint',
        'IDR' => 'Indonesian Rupiah',
        'ILS' => 'Israeli New Shekel',
        'INR' => 'Indian Rupee',
        'ISK' => 'Icelandic Króna',
        'JPY' => 'Japanese Yen',
        'KRW' => 'South Korean Won',
        'MXN' => 'Mexican Peso',
        'MYR' => 'Malaysian Ringgit',
        'NOK' => 'Norwegian Krone',
        'NZD' => 'New Zealand Dollar',
        'PHP' => 'Philippine Peso',
        'PLN' => 'Polish Złoty',
        'RON' => 'Romanian Leu',
        'RUB' => 'Russian Ruble',
        'SEK' => 'Swedish Krona',
        'SGD' => 'Singapore Dollar',
        'THB' => 'Thai Baht',
        'TRY' => 'Turkish Lira',
        'USD' => 'United States Dollar',
        'ZAR' => 'South African Rand',
    ];
}

function get_asset_class(): array
{
    return [
        'Property' => 'Property',
        'Technology' => 'Technology',
        'Diversified' => 'Diversified',
        'Forestry' => 'Forestry',
        'Residential' => 'Residential',
    ];
}

function get_yes_no(): array
{
    return [
        'yes' => 'Yes',
        'no' => 'No',
    ];
}

function login_page_designs(): array
{
    return [
        'ic' => 'IC Market',
        'basic' => 'Basic',
        'td' => 'Trade Nation',
        'em' => 'Equity Markets',
        'propfirm' => 'Prop Firm',
        'propfirm_light' => 'Prop Firm (Light)',
    ];
}

function eas_type(): array
{
    return [
        'expert_advisor' => 'Expert Advisor',
        'others' => 'Other',
    ];
}
function leverage_system(): array
{
    return [
        'asset_category_based' => 'Asset Category Based',
        'plan_based' => 'Plan Based',
    ];
}

function dashboard_page_designs(): array
{
    return [
        'basic' => 'Basic',
        'analytics' => 'Analytics',
    ];
}

function admin_dashboard_designs(): array
{
    return [
        'analytics' => 'Dashboard 1',
    ];
}

function get_chat_id(string $department = 'general')
{
    if (app()->bound('company') && app('company')) {
        return app('company')->telegramChatIdForDepartment($department);
    }

    return config('app.telegram_chat_id');
}

function register_page_design(): array
{
    return [
        'ic' => 'IC Market',
        'basic' => 'Basic',
        'td' => 'Trade Nation',
        'em' => 'Equity Markets',
        'propfirm' => 'Prop Firm',
        'propfirm_light' => 'Prop Firm (Light)',
    ];
}

function forgot_password_page_designs(): array
{
    return [
        'basic' => 'Basic',
        'propfirm' => 'Prop Firm',
        'propfirm_light' => 'Prop Firm (Light)',
    ];
}

function admin_login_designs(): array
{
    return [
        'default' => 'Default',
        'apex' => 'Apex (Dark)',
    ];
}

function webtrader_login_page_designs(): array
{
    return [
        'default' => 'Default (Trading Terminal, Dark)',
        'terminal' => 'Trading Terminal (Light)',
        'terminal_dark' => 'Trading Terminal (Dark)',
    ];
}

function get_theme(): array
{
    return [
        'default' => 'Default',
        'central' => 'Central',
    ];
}

function is_central(): bool
{
    return settings('theme') == 'central';
}

function use_frontend(): bool
{
    return config('app.use_frontend', false);
}

function generateUniqueTradeId(): string
{
    do {
        $tradeId = (string) random_int(10_000_000, 99_999_999);
    } while (Trade::query()->where('trade_id', $tradeId)->exists());

    return $tradeId;
}

function getKYCStatus($status)
{
    if ($status === 'approved') {
        return 'bg-green-600 text-white';
    }
    if ($status === 'rejected') {
        return 'bg-red-600 text-white';
    }

    return 'bg-yellow-100 text-yellow-800';
}

function bg_primary()
{
    if (settings('theme') == 'central') {
        return 'bg-meta-1';
    }

    return 'bg-green-500';
}

/**
 * Admin deposit/withdraw form's "Account" dropdown. When passed a user,
 * lists each of their real trading accounts individually (so the admin sees
 * exactly what's attached to this user and picks explicitly), plus the
 * client-level Credit Wallet, Bonus Wallet, and Spot Wallet -- all four are
 * directly creditable/debitable by an admin here. Falls back to the generic
 * "Account Wallet" option when no user is given, or the user has no trading
 * accounts yet, so existing callers keep working.
 *
 * Per PR #99 ("Move Credit/Bonus to client-level wallets"), Credit and Bonus
 * are client-level wallets again, not a per-trading-account component --
 * this always returns that flat list now. An earlier revision (PR #98,
 * 2026-07-20) briefly branched this on a `wallet_components_enabled` flag
 * into a two-step account-then-component picker (accountGroups(), since
 * removed) instead; PR #99 superseded that design in the rest of the
 * codebase (User.php's balance/credit resolution no longer checks the flag
 * at all) but never updated this view, so ~51 tenants that had the flag on
 * from the earlier rollout kept seeing the stale two-step picker until this
 * fix. `wallet_components_enabled` is no longer read here or from
 * DepositController::create()/store() for this picker -- it may still be
 * read elsewhere for other, unrelated purposes.
 */
function accounts(?\App\Models\User $user = null): array
{
    $walletOptions = company_settings('mini_wallet', 'no') == 'yes'
        ? ['credit_wallet' => company_settings('custom_wallet')]
        : ['credit_wallet' => 'Credit Wallet', 'bonus_wallet' => 'Bonus Wallet'];

    $tradingAccountOptions = [];

    if ($user) {
        foreach ($user->tradingAccounts as $tradingAccount) {
            $label = sprintf(
                '%s Account #%s (%s)',
                ucfirst($tradingAccount->account_type),
                $tradingAccount->account_id,
                format_price($tradingAccount->balance)
            );
            $tradingAccountOptions['trading_account:'.$tradingAccount->id] = $label;
        }
    }

    if (empty($tradingAccountOptions)) {
        $tradingAccountOptions = ['account_wallet' => 'Account Wallet'];
    }

    return $tradingAccountOptions + $walletOptions + ['spot_wallet' => 'Spot Wallet'];
}

function transfer_accounts(): array
{
    return [
        'credit_wallet' => 'Credit Wallet',
        'bonus_wallet' => 'Bonus Wallet',
    ];
}

function receive_accounts(): array
{
    return [
        'account_wallet' => 'Account Wallet',
    ];
}

function has_smart_contract(): bool
{
    return settings('enable_smart_contract', 'yes') == 'yes';
}

function has_affiliate(): bool
{
    return company_settings('enable_affiliate', settings('enable_affiliate', 'no')) == 'yes';
}

function has_ecn(): bool
{
    return settings('enable_ecn', 'yes') == 'yes';
}

function has_accounts(): bool
{
    return settings('enable_accounts', 'yes') == 'yes';
}

function get_issue_type(): array
{
    return [
        'Commercial Property' => 'Commercial Property',
        'Late Stage Capital' => 'Late Stage Capital',
        'Managed Investment Scheme' => 'Managed Investment Scheme',
        'Agri-Business' => 'Agri-Business',
        'Early Stage Capital' => 'Early Stage Capital',
        'Venture Capital' => 'Venture Capital',
        'Residential Property' => 'Residential Property',
    ];
}
function get_issue(): array
{
    return [
        'Wholesale' => 'Wholesale',
    ];
}

function get_asset_types(): array
{
    return [
        'crypto' => 'Crypto',
        'forex' => 'Forex',
        'stocks' => 'Stock',
        'commodities' => 'Commodities',
        'indices' => 'Indices',
    ];
}

function get_withdrawal_methods()
{
    return [
        'crypto' => 'Crypto',
        'wire_transfer' => 'Wire Transfer',
    ];
}

function get_desk()
{
    return [
        '1' => 'Desk 1',
        '2' => 'Desk 2',
    ];
}
function get_brand()
{
    return [
        '1' => 'Brand 1',
        '2' => 'Brand 2',
        '3' => 'Brand 3',
        '4' => 'Brand 4',
        '5' => 'Brand 5',
    ];
}

function get_payment_status()
{
    return [
        'approved' => 'Approved',
        'pending' => 'Pending',
        'declined' => 'Decline',
    ];
}

function get_status_message_class($status): string
{
    if ($status == 'approved') {
        return 'bg-green-600 text-white';
    }
    if ($status == 'pending') {
        return 'bg-yellow-100 text-yellow-800';
    }
    if ($status == 'rejected') {
        return 'bg-red-600 text-yellow-800';
    }

    return 'bg-yellow-100 text-yellow-800';
}

function get_status(): array
{
    if (\auth()->user()->hasRole(['admin', 'superadmin', 'manager'])) {

        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'suspended' => 'Suspended',
            'active' => 'Active',
            'draft' => 'Draft',
        ];
    } else {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'draft' => 'Draft',
        ];
    }

}
function get_user_status(): array
{
    return [
        'pending' => 'Pending',
        'deactivated' => 'Deactivated',
        'active' => 'Active',
    ];

}

function get_customer_approval_statuses(): array
{
    return [
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];
}

function embedImageFromUrl($url): string
{
    $url = getFullImage($url);
    $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));

    return storage_path('app/public/'.$path);
}

function get_inv_status(): array
{
    return [
        'request' => 'Request',
        'pending' => 'Pending',
        'completed' => 'Completed',
        'active' => 'Active',
    ];

}
function get_schedule(): array
{
    return [
        'instant' => 'Instant',
        'next_day' => 'Next Day',
        'manual' => 'Manual',
    ];
}

function get_content_section($page, $title)
{
    $content = $page->contents->where('title', $title)->first();
    if ($content) {
        return $content;
    }

    return false;
}

function get_content_category($page, $skip = null)
{
    $content = null;

    if ($skip) {
        $content = $page->contents->where('type', 'products_by_category')->skip($skip)->first();
    } else {
        $content = $page->contents->where('type', 'products_by_category')->first();
    }

    if ($content) {
        if (is_string($content->content)) {
            $cat = Cache::remember('category_by_slug_'.$content->id, now()->addHours(5), function () use ($content) {
                return Category::findBySlug($content->content);
            });
            if ($cat) {
                $content->category_id = $cat->id;

                return $content;
            }
        }

    }

    return false;
}

function get_home_sliders($page)
{
    $content = $page->contents->where('title', 'home_page_main_sliders')->first();

    if ($content) {
        return $content->content;
    }

    return false;
}

function user_tabs(): array
{
    $tabs = [
        ['id' => 'profile', 'title' => 'Profile', 'component' => 'profile'],
        //        ['id' => 'notes', 'title' => 'Notes', 'component' => 'notes'],
        ['id' => 'position', 'title' => 'Open Positions', 'component' => 'position'],
        ['id' => 'closed_positions', 'title' => 'Closed Positions', 'component' => 'closed_positions'],
        ['id' => 'transactions', 'title' => 'Transactions', 'component' => 'transactions'],
        ['id' => 'scheduled_trades', 'title' => 'AI Schedule', 'component' => 'scheduled_trades'],
        //        ['id' => 'wallet', 'title' => 'Wallet', 'component' => 'wallet'],
        //        ['id' => 'transfer', 'title' => 'Transfer', 'component' => 'transfer'],
        //        ['id' => 'post', 'title' => 'Post', 'component' => 'post'],
        //        ['id' => 'kyc', 'title' => 'KYC', 'component' => 'kyc'],
        ['id' => 'logs', 'title' => 'Logs', 'component' => 'logs'],
    ];

    if (module_enabled('enable_ai_recommendations')) {
        $tabs[] = ['id' => 'ai_recommendations', 'title' => 'AI Recommendations', 'component' => 'ai_recommendations'];
    }

    $bitraceEnabled = company_settings('bitrace_enabled', false);
    if (filter_var($bitraceEnabled, FILTER_VALIDATE_BOOLEAN)) {
        $tabs[] = ['id' => 'recovery', 'title' => 'Cases', 'component' => 'recovery'];
    }

    $requireAiAgreement = company_settings('require_ai_trading_agreement', false);
    if (filter_var($requireAiAgreement, FILTER_VALIDATE_BOOLEAN)) {
        $tabs[] = ['id' => 'ai_agreement', 'title' => 'Investment Agreement', 'component' => 'ai_agreement'];
    }

    return $tabs;
}

function mini_user_tabs(): array
{
    if (\App\Services\RestrictedManagerAccess::active()) {
        return [
            ['id' => 'profile', 'title' => 'Profile', 'component' => 'restricted_profile'],
            ['id' => 'balance_bonus', 'title' => 'Portfolio', 'component' => 'balance_bonus'],
            ['id' => 'trading_accounts', 'title' => 'Trading Accounts', 'component' => 'trading_accounts'],
            ['id' => 'transactions', 'title' => 'Deposit', 'component' => 'transactions'],
            ['id' => 'withdrawals', 'title' => 'Withdrawals', 'component' => 'withdrawals'],
            ['id' => 'activities', 'title' => 'Activities', 'component' => 'activities'],
        ];
    }

    $propfirmEnabled = filter_var(company_settings('propfirm_enabled', false), FILTER_VALIDATE_BOOLEAN);
    // "Auto Profit" (can_trade/copy_trader/ai_trade toggles) and "AI Schedule"
    // are only gated behind their own enabled-flag for Prop Firm -- brokerage
    // keeps these tabs unconditionally, unchanged from before.
    $autoProfitEnabled = module_enabled('enable_autotrader') || module_enabled('ai_trade');

    $tabs = [
        // profile_propfirm.blade.php adds a country flag next to Country --
        // brokerage's profile tab is unchanged.
        ['id' => 'profile', 'title' => 'Profile', 'component' => $propfirmEnabled ? 'profile_propfirm' : 'profile'],
    ];

    // Challenges/Payouts moved right after Profile (before Accounts) for
    // Prop Firm, per Nick's ask that they "start early" on the tab bar --
    // previously these were appended at the very end, then moved to right
    // after Transactions, now moved earlier still. Withdrawals -- called
    // Payouts in Prop Firm -- is replaced by the Payouts tab rather than
    // shown twice.
    if ($propfirmEnabled) {
        $tabs[] = ['id' => 'challenges', 'title' => 'Challenges', 'component' => 'challenges'];
        $tabs[] = ['id' => 'payouts', 'title' => 'Payouts', 'component' => 'payouts'];
    }

    // "Accounts" (generic wallet list, distinct from Trading Accounts below)
    // removed from the tab bar for now -- superseded by Portfolio's "Manage
    // Wallet" action and the dedicated Trading Accounts tab, and kept the
    // "Accounts" name would collide with the Portfolio rename below.

    // Trades/Trading Accounts are a brokerage concept -- Prop Firm customers
    // trade inside challenges/funded accounts instead, so these are hidden
    // there rather than just reordered. Portfolio moved to right after
    // Profile (before Trading Accounts) for brokerage, per Nick's ask --
    // Prop Firm keeps its own explicit order above (Challenges/Payouts
    // first) untouched, so Portfolio stays where it already was there.
    if (! $propfirmEnabled) {
        $tabs[] = ['id' => 'balance_bonus', 'title' => 'Portfolio', 'component' => 'balance_bonus'];
        $tabs[] = ['id' => 'trading_accounts', 'title' => 'Trading Accounts', 'component' => 'trading_accounts'];
        $tabs[] = ['id' => 'trades', 'title' => 'Trades', 'component' => 'trades'];
    } else {
        $tabs[] = ['id' => 'balance_bonus', 'title' => 'Portfolio', 'component' => 'balance_bonus'];
    }

    $tabs[] = ['id' => 'transactions', 'title' => 'Deposit', 'component' => 'transactions'];

    if (! $propfirmEnabled) {
        $tabs[] = ['id' => 'withdrawals', 'title' => 'Withdrawals', 'component' => 'withdrawals'];
    }

    $tabs[] = ['id' => 'activities', 'title' => 'Activities', 'component' => 'activities'];
    $tabs[] = ['id' => 'messages', 'title' => 'Messages', 'component' => 'messages'];

    if (! $propfirmEnabled || $autoProfitEnabled) {
        $tabs[] = ['id' => 'auto_profit', 'title' => 'Auto Profit', 'component' => 'auto_profit'];
        $tabs[] = ['id' => 'scheduled_trades', 'title' => 'AI Schedule', 'component' => 'scheduled_trades'];
    }

    $tabs[] = ['id' => 'notes', 'title' => 'Notes', 'component' => 'notes'];
    $tabs[] = ['id' => 'kyc', 'title' => 'KYC', 'component' => 'kyc'];

    if (module_enabled('enable_ai_recommendations')) {
        $tabs[] = ['id' => 'ai_recommendations', 'title' => 'AI Recommendations', 'component' => 'ai_recommendations'];
    }

    $bitraceEnabled = company_settings('bitrace_enabled', false);
    if (filter_var($bitraceEnabled, FILTER_VALIDATE_BOOLEAN)) {
        $tabs[] = ['id' => 'recovery', 'title' => 'Cases', 'component' => 'recovery'];
    }

    $requireAiAgreement = company_settings('require_ai_trading_agreement', false);
    if (filter_var($requireAiAgreement, FILTER_VALIDATE_BOOLEAN)) {
        $tabs[] = ['id' => 'ai_agreement', 'title' => 'Investment Agreement', 'component' => 'ai_agreement'];
    }

    return $tabs;
}

function get_today_deal($page)
{
    $content = $page->contents->where('title', 'today_deal')->first();
    if ($content) {
        $cacheKey = 'today_deal_'.$content->updated_at;

        return Cache::rememberForever($cacheKey, function () use ($content) {
            $product_ids = $content->content;
            if (is_array($product_ids)) {
                return \App\Models\Product::active()->whereIn('id', $product_ids)->get();
            }

            return [];
        });
    }

    return false;
}

function getTags(): array
{
    return [
        ['id' => 1, 'name' => 'Electronic'],
        ['id' => 2, 'name' => 'Mobile'],
        ['id' => 3, 'name' => 'Iphone'],
        ['id' => 4, 'name' => 'Printer'],
        ['id' => 5, 'name' => 'Office'],
        ['id' => 6, 'name' => 'IT'],
    ];
}

function portfolio_plan_categories(): array
{
    return [
        'American Stock' => 'American Stock',
    ];
}
function periods_in_month(): array
{
    return [
        1 => '1 Month',
        3 => '3 Months',
        6 => '6 Months',
        12 => '1 Year',
        24 => '2 Years',
    ];
}

function getWeights(): array
{
    return [
        ['id' => 1, 'name' => '1KG'],
        ['id' => 2, 'name' => '2KG'],
        ['id' => 3, 'name' => '3KG'],
        ['id' => 4, 'name' => '4KG'],
        ['id' => 5, 'name' => '5KG'],
        ['id' => 6, 'name' => '6KG'],
    ];
}

function token_user_id($key)
{
    $token = Token::whereToken($key)->first();
    if (! $token) {
        return null;
    }

    return $token->user_id;
}

function super_menu_list(): array
{
    return [
        [
            'title' => 'MENU',
            'menus' => [
                [
                    'url' => route('super.dashboard'),
                    'route' => 'super.dashboard',
                    'text' => 'Dashboard',
                    'icon' => 'radix-dashboard',
                ],

                [
                    'url' => route('super.platforms.index'),
                    'route' => 'super.platforms.index',
                    'text' => 'Platforms',
                    'icon' => 'gmdi-list-alt-tt',
                ],
                [
                    'url' => route('super.companies.index'),
                    'route' => 'super.companies.index',
                    'text' => 'Companies',
                    'icon' => 'heroicon-o-building-office-2',
                ],

                [
                    'url' => route('super.domains.index'),
                    'route' => 'super.domains.index',
                    'text' => 'Domains',
                    'icon' => 'gmdi-domain-verification',
                ],

                [
                    'url' => route('super.assets.index'),
                    'route' => 'super.assets.index',
                    'text' => 'Assets',
                    'icon' => 'heroicon-m-chart-bar',
                ],

                [
                    'url' => route('super.experts.index'),
                    'route' => 'super.experts.index',
                    'text' => 'Expert Advisors',
                    'icon' => 'heroicon-s-cog-8-tooth',
                ],
                [
                    'url' => route('super.plans.index'),
                    'route' => 'super.plans.index',
                    'text' => 'Tenant Plans',
                    'icon' => 'heroicon-m-credit-card',
                ],
                [
                    'url' => route('super.plan_features.index'),
                    'route' => 'super.plan_features.index',
                    'text' => 'Tenant Plan Features',
                    'icon' => 'heroicon-m-adjustments-horizontal',
                ],
                [
                    'route' => 'super.customer_plan_features.index',
                    'url' => route('super.customer_plan_features.index'),
                    'text' => 'Customer Plan Features',
                    'icon' => 'heroicon-m-rectangle-stack',
                ],

                //
                //                [
                //                    'url' => route('admin.settings.index'),
                //                    'route' => 'admin.settings.index',
                //                    'text' => 'Settings',
                //                    'icon' => 'heroicon-s-cog-8-tooth',
                //                ],

            ],
        ],
    ];
}

function tenant_menu_list(): array
{
    return [
        [
            'title' => 'Workspace',
            'menus' => [
                [
                    'url' => route('super.dashboard'),
                    'route' => 'super.dashboard',
                    'text' => 'Dashboard',
                    'icon' => 'radix-dashboard',
                ],
                [
                    'url' => route('super.platforms.index'),
                    'route' => 'super.platforms.index',
                    'text' => 'Platforms',
                    'icon' => 'gmdi-list-alt-tt',
                ],
                [
                    'url' => route('super.domains.index'),
                    'route' => 'super.domains.index',
                    'text' => 'Domains',
                    'icon' => 'gmdi-domain-verification',
                ],
            ],
        ],
    ];
}

function menu_lists(): array
{
    if (isMini()) {

    }

    $menus = [
        [
            'title' => 'MENU',
            'menus' => [
                [
                    'url' => route('admin.dashboard'),
                    'route' => 'admin.dashboard',
                    'text' => 'Dashboard',
                    'icon' => 'radix-dashboard',
                ],

                [
                    'url' => route('admin.wallets.index'),
                    'route' => 'admin.wallets.index',
                    'text' => 'Wallet',
                    'icon' => 'gmdi-wallet-tt',
                ],

                [
                    'url' => route('admin.users.agents'),
                    'route' => 'admin.users.agents',
                    'text' => 'Agents',
                    'icon' => 'gmdi-support-agent',
                ],
            ],
        ],
    ];

    $api_key = [
        'route' => 'admin.tokens.index',
        'text' => 'Affliate API Keys',
        'icon' => 'heroicon-o-square-3-stack-3d',
    ];

    $menus[0]['menus'] = array_merge($menus[0]['menus'], [
        [
            'url' => route('admin.users.customers'),
            'route' => 'admin.users.customers',
            'text' => 'Customers',
            'icon' => 'radix-person',
        ],
        [
            'url' => route('admin.user.leads'),
            'route' => 'admin.user.leads',
            'text' => 'Leads',
            'icon' => 'radix-person',
        ],
        [
            'url' => route('admin.chat.index'),
            'route' => 'admin.chat.index',
            'text' => 'Internal Chat',
            'icon' => 'gmdi-filter-list-alt',
        ],
        [
            'url' => route('admin.assets.index'),
            'route' => 'admin.assets.index',
            'text' => 'Assets',
            'icon' => 'heroicon-m-chart-bar',
        ],

        [
            'url' => route('admin.user.logs'),
            'route' => 'admin.user.logs',
            'text' => 'Logs',
            'icon' => 'carbon-list-dropdown'],

        [
            'route' => 'admin.users.leads',
            'text' => 'Lead Management',
            'icon' => 'gmdi-featured-play-list-r',
            'menus' => [
                [
                    'route' => 'admin.users.leads',
                    'text' => 'Leads',
                ], [
                    'route' => 'admin.users.affiliates',
                    'text' => 'Affiliates',
                ], [
                    'route' => 'admin.data.brands',
                    'text' => 'Brands',
                ], [
                    'route' => 'admin.data.status',
                    'text' => 'Status',
                ], [
                    'route' => 'admin.data.campaigns',
                    'text' => 'Campaigns',
                ], [
                    'route' => 'admin.data.sources',
                    'text' => 'Sources',
                ],
            ],

        ],

        [
            'route' => '##',
            'text' => 'Risk Management',
            'icon' => 'gmdi-manage-accounts-s',
            'menus' => [
                [
                    'route' => 'admin.trades.index',
                    'text' => 'Positions',
                ], [
                    'route' => 'admin.roles.index',
                    'text' => 'Transactions',
                ], [
                    'route' => 'admin.roles.index',
                    'text' => 'Wallet Transactions',
                ],
            ],

        ],

        [
            'route' => 'admin.all_withdrawals',
            'text' => 'Payment Audit',
            'icon' => 'gmdi-security-tt',
            'menus' => [
                [
                    'route' => 'admin.all_withdrawals',
                    'text' => 'All Withdrawals',
                ], [
                    'route' => 'admin.all_deposits',
                    'text' => 'All Deposits',
                ],
            ],

        ],

        [
            'url' => route('admin.chat.index'),
            'route' => 'admin.chat.index',
            'text' => 'Articles',
            'icon' => 'gmdi-post-add-o',

        ],

        [
            'url' => route('admin.settings.index'),
            'route' => 'admin.settings.index',
            'text' => 'Settings',
            'icon' => 'heroicon-s-cog-8-tooth',
        ],
        [
            'url' => '#',
            'route' => 'admin.settings.index',
            'text' => 'Data',
            'icon' => 'carbon-settings-adjust',
            'menus' => [
                ['route' => 'admin.data.teams', 'text' => 'Teams'],
                ['route' => 'admin.data.desks', 'text' => 'Desks'],
                ['route' => 'admin.data.brands', 'text' => 'Brands'],
                ['route' => 'admin.data.labels', 'text' => 'Labels'],
            ],
        ],

    ]);

    if (has_affiliate()) {
        $menus[0]['menus'][] = $api_key;
    }

    return $menus;

}

function menu_list()
{
    $menuData = [
        [
            'title' => 'MENU',
            'menus' => [
                [
                    'url' => route('admin.dashboard'),
                    'route' => 'admin.dashboard',
                    'text' => 'Dashboard',
                    'icon' => 'radix-dashboard',
                    'active' => true,
                ],
                [
                    'url' => route('admin.users.customers'),
                    'route' => 'admin.users.customers',
                    'text' => 'Customers',
                    'icon' => 'radix-person',
                    'active' => true,
                ],

                [
                    'url' => route('admin.users.leads'),
                    'route' => 'admin.users.leads',
                    'text' => 'Leads',
                    'icon' => 'gmdi-support-agent',
                    'active' => module_enabled('enable_leads'),
                ],

                [
                    'url' => route('admin.campaigns.index'),
                    'route' => 'admin.campaigns.index',
                    'text' => 'Marketing',
                    'icon' => 'heroicon-m-arrow-trending-up',
                    'active' => true,
                ],

                [
                    'url' => route('admin.propfirm.plans.index'),
                    'route' => 'admin.propfirm.plans.index',
                    'text' => 'Prop Firm',
                    'icon' => 'heroicon-m-trophy',
                    'active' => module_enabled('propfirm_enabled'),
                ],

                [
                    'url' => route('admin.support-tickets.index'),
                    'route' => 'admin.support-tickets.index',
                    'text' => 'Support Tickets',
                    'icon' => 'heroicon-m-lifebuoy',
                    'active' => true,
                ],

                [
                    'url' => route('admin.products.index'),
                    'route' => 'admin.products.index',
                    'text' => 'Products',
                    'icon' => 'heroicon-m-shopping-bag',
                    'active' => true,
                ],

                [
                    'url' => route('admin.welcome-messages.index'),
                    'route' => 'admin.welcome-messages.index',
                    'text' => 'Email Templates',
                    'icon' => 'heroicon-m-envelope',
                    'active' => true,
                ],

                [
                    'url' => module_enabled('propfirm_enabled') ? route('admin.users.agents') : route('admin.users.managers'),
                    'route' => module_enabled('propfirm_enabled') ? 'admin.users.agents' : 'admin.users.managers',
                    'text' => module_enabled('propfirm_enabled') ? 'Agents' : 'Managers',
                    'icon' => 'gmdi-people-alt',
                    // Prop Firm tenants get the Manager/Agent hierarchy on by
                    // default -- brokerage still needs the explicit toggle.
                    'active' => module_enabled('propfirm_enabled') || module_enabled('enable_managers'),
                ],
                [
                    // Prop Firm's two-tier hierarchy (Manager -> Agent ->
                    // Customer) needs its own separate entry for the top
                    // tier -- the item above only ever shows one of
                    // Managers/Agents, never both, and someone (the tenant
                    // admin) needs to see/create the Managers themselves.
                    'url' => route('admin.users.managers'),
                    'route' => 'admin.users.managers',
                    'text' => 'Managers',
                    'icon' => 'gmdi-people-alt',
                    'active' => module_enabled('propfirm_enabled'),
                ],
                [
                    'url' => route('admin.users.affiliates'),
                    'route' => 'admin.users.affiliates',
                    'text' => 'Affiliates',
                    'icon' => 'gmdi-supervised-user-circle-r',
                    'active' => module_enabled('enable_affiliate'),
                ],
                [
                    'url' => route('admin.tokens.index'),
                    'route' => 'admin.tokens.index',
                    'text' => 'Affiliate API Keys',
                    'icon' => 'gmdi-supervised-user-circle-r',
                    'active' => module_enabled('enable_affiliate'),
                ],

                [
                    'url' => route('admin.users.supports'),
                    'route' => 'admin.users.supports',
                    'text' => 'Chat Support',
                    'icon' => 'heroicon-s-chat-bubble-oval-left-ellipsis',
                    'active' => true,
                ],

                [
                    // Same auto-login-token mechanism as the header chat
                    // icon, rendered inline via an iframe instead of a full
                    // redirect away to the client-facing frontend app.
                    'url' => route('admin.live_chat'),
                    'route' => 'admin.live_chat',
                    'text' => 'Live Chat',
                    'icon' => 'heroicon-o-chat-bubble-left-right',
                    'active' => module_enabled('propfirm_enabled'),
                ],

                [
                    'url' => route('admin.announcements.index'),
                    'route' => 'admin.announcements.index',
                    'text' => 'News Feed',
                    'icon' => 'heroicon-o-megaphone',
                    'active' => true,
                ],

                [
                    'url' => route('admin.users.admins'),
                    'route' => 'admin.users.admins',
                    'text' => 'Admins',
                    'icon' => 'gmdi-verified-user-s',
                    'active' => module_enabled('enable_admins'),
                ],

                [
                    'url' => route('admin.user.logs'),
                    'route' => 'admin.user.logs',
                    'text' => 'Logs',
                    'icon' => 'carbon-list-dropdown',
                    'active' => true,
                ],
                [
                    'url' => route('admin.settings.index'),
                    'route' => 'admin.settings.index',
                    'text' => 'Settings',
                    'icon' => 'heroicon-s-cog-8-tooth',
                    'active' => true,

                ],

            ],
        ],
    ];

    foreach ($menuData as $key => $menuGroup) {
        $menuData[$key]['menus'] = array_filter($menuGroup['menus'], function ($menuItem) {
            return $menuItem['active'] === true;
        });
    }

    return $menuData;
}

function getFullImage($img): array|string
{
    return str_replace('/thumbs', '', $img);
}

function round_number($num): float|int
{
    return ceil($num / 5) * 5;
}
function vendor_id()
{
    $user = \auth()->user();

    $cacheKey = 'vendor_id_'.$user->id;
    $vendorId = Cache::get($cacheKey);

    if (! $vendorId) {
        $vendorId = $user->vendor->id;
        Cache::put($cacheKey, $vendorId, now()->addHours(24));
    }

    return $vendorId;
}

function getOrderTimeline($vendor): array
{
    return [
        ['title' => 'Order Received', 'description' => "Waiting for $vendor->name to accept your order.", 'time' => null],
        ['title' => 'Order in Progress', 'description' => 'Your order is accepted and being prepared, it’ll be ready soon!', 'time' => null],
        ['title' => 'Rider Assigned', 'description' => 'A rider has been assigned to pick up your order.', 'time' => null],
        ['title' => 'Rider at Pickup', 'description' => 'The rider has arrived to pick up your order.', 'time' => null],
        ['title' => 'On the Way', 'description' => 'Your rider is on the way to deliver your order.', 'time' => null],
        ['title' => 'Almost There', 'description' => 'Your order is almost at your location.', 'time' => null],
        ['title' => 'Delivered', 'description' => 'Your order has been delivered. Enjoy!', 'time' => null],
    ];
}

function getService($id)
{
    return Service::select('name', 'id', 'type')->where('id', $id)->firstOrFail();
}

function show_chart_date($p = null)
{
    if (! $p) {
        return Carbon::today()->format('d-m-Y');
    }
    if ($p == 'd') {
        return Carbon::today()->format('d-m-Y');
    }
    if ($p == 'w') {
        return Carbon::now()->subWeek()->format('d-m-Y').' - '.Carbon::today()->format('d-m-Y');
    }
    if ($p == 'm') {
        return Carbon::now()->subMonth()->format('d-m-Y').' - '.Carbon::today()->format('d-m-Y');
    }
    if ($p == 'y') {
        return Carbon::now()->subYear()->format('d-m-Y').' - '.Carbon::today()->format('d-m-Y');
    }
}

function generateDeliveryPin()
{
    return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

}

function convertPinToArray($pin)
{
    return str_split($pin);
}

/**
 * Looks up a customer's stored country name (freeform, lowercase -- e.g.
 * "canada", "morocco") against public/config/countries.json (the same file
 * countryOptions() already falls back to -- the countries DB table is
 * empty) to get its ISO alpha-2 code and dial code. No phone-parsing
 * library is available in this app, so this doesn't try to strip/reformat
 * the stored phone digits -- it just surfaces the dial code alongside the
 * number as-is, which is honest given there's nothing to validate against.
 */
function country_flag_and_dial(?string $countryName): ?array
{
    static $map = null;

    if ($map === null) {
        $map = [];
        $path = public_path('config/countries.json');

        if (file_exists($path)) {
            $data = json_decode((string) file_get_contents($path), true) ?? [];

            foreach ($data as $c) {
                if (! empty($c['name']) && ! empty($c['code'])) {
                    $map[strtolower($c['name'])] = [
                        'code' => strtoupper($c['code']),
                        'dial_code' => $c['dial_code'] ?? null,
                    ];
                }
            }
        }
    }

    $name = strtolower(trim((string) $countryName));

    if ($name === '' || ! isset($map[$name])) {
        return null;
    }

    $entry = $map[$name];
    $code = $entry['code'];

    if (strlen($code) !== 2) {
        return array_merge($entry, ['flag' => '', 'flag_url' => null]);
    }

    // Emoji flags (regional indicator symbol pairs) don't actually render as
    // a flag glyph everywhere -- several OS/browser font combinations show
    // the raw two letters instead, which is what was happening here. A real
    // flag image is guaranteed to render the same everywhere. flagcdn.com is
    // a long-standing free public flag CDN, no key required -- there are no
    // flag assets bundled in this app to use instead.
    $flag = '';
    foreach (str_split($code) as $letter) {
        $flag .= mb_chr(0x1F1E6 + (ord($letter) - ord('A')), 'UTF-8');
    }

    return array_merge($entry, [
        'flag' => $flag,
        'flag_url' => 'https://flagcdn.com/24x18/'.strtolower($code).'.png',
    ]);
}

function formatPhone($phone): string
{
    $number = $phone;
    if (str_starts_with($number, '0')) {
        return substr($number, 1);
    }
    if (str_starts_with($number, '+234')) {
        return substr($number, 4);
    }

    return $phone;
}

function fullPhone($phone, $country_code = '+234'): string
{
    $number = formatPhone($phone);
    if (strlen($number) > 10) {
        return $number;
    }

    return $country_code.$number;
}

function getWeightByService($service_type): float|int
{
    if ($service_type == 'food') {
        return 0.4;
    } else {
        return 1;
    }
}

function generateSKU($text): string
{
    $title = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
    $words = explode(' ', $title);
    $sku = '';
    foreach ($words as $word) {
        $sku .= strtoupper(substr($word, 0, 3));
    }
    $sku .= '-'.strtoupper(uniqid());

    return $sku;
}

function getEnvCode(): string
{
    if (config('app.app_branch') == 'live') {
        return 'lv';
    } else {
        return 'st';
    }
}

function app_branch(): string
{
    return config('app.app_branch');
}

function insertSubdirectory($originalPath, $subdirectory = 'thumbnail'): string
{
    $pathParts = explode('/', $originalPath);
    array_splice($pathParts, 0, 0, $subdirectory);

    return implode('/', $pathParts);
}

function appendPrefixToFilename($originalPath, $prefix = 'thumbs_'): string
{
    $lastSlashPos = strrpos($originalPath, '/');
    if ($lastSlashPos !== false) {
        return substr($originalPath, 0, $lastSlashPos + 1).$prefix.substr($originalPath, $lastSlashPos + 1);
    }

    return $prefix.$originalPath;
}

function renamePhotoFile($originalPath): string
{
    return str_replace('photos/', 'thumb_photos/', $originalPath);

}

function getPhotoThumb($path): array|string
{
    $path = str_replace('/thumbs/', '/', $path);

    return str_replace('photos/', 'thumb_photos/', $path);

}

function is_local(): bool
{
    return ! app()->isProduction();
}

function mask_phone($phone): string
{
    if (empty($phone) || strlen($phone) < 4 || is_admin() || ! module_enabled('mask_phone')) {
        return $phone;
    }

    $visiblePart = substr($phone, 0, 3);
    $maskedPart = str_repeat('*', strlen($phone) - 3);

    return $visiblePart.$maskedPart;
}

if (! function_exists('plan_limits')) {
    /**
     * Plan-limit resolver for a company (defaults to the bound tenant company).
     */
    function plan_limits(?\App\Models\Company $company = null): \App\Services\PlanLimitService
    {
        return \App\Services\PlanLimitService::for($company);
    }
}

if (! function_exists('plan_limit')) {
    /**
     * Resolved value for a single plan-limit key (-1 = unlimited).
     */
    function plan_limit(string $key, ?\App\Models\Company $company = null): int
    {
        return \App\Services\PlanLimitService::for($company)->get($key);
    }
}

if (! function_exists('enforce_plan_limit')) {
    /**
     * Throw an upgrade-required exception when a plan-limit key's cap is reached.
     * $used is the caller-computed current usage for the relevant scope.
     *
     * @throws \App\Exceptions\PlanLimitExceededException
     */
    function enforce_plan_limit(string $key, int $used, ?\App\Models\Company $company = null): void
    {
        \App\Services\PlanLimitService::for($company)->enforce($key, $used);
    }
}

if (! function_exists('plan_limit_definitions')) {
    /**
     * Registry limit keys with labels + defaults, for the superadmin plan editor.
     *
     * @return array<int, array{key:string,label:string,default:int}>
     */
    function plan_limit_definitions(): array
    {
        $labels = config('plan_limits.labels', []);

        return collect(config('plan_limits.defaults', []))
            ->map(fn ($default, string $key) => [
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'default' => (int) $default,
            ])
            ->values()
            ->all();
    }
}

if (! function_exists('plan_open_trade_context')) {
    /**
     * Open-trade plan-limit context for the trade screen so the frontend can
     * show remaining slots and a max-duration countdown. Null values = unlimited.
     */
    function plan_open_trade_context(\App\Models\User $user, $tradingAccount = null): array
    {
        $limits = \App\Services\PlanLimitService::for();

        $openForUser = \App\Models\Trade::query()
            ->where('user_id', $user->id)
            ->where('status', 0)
            ->count();

        $context = [
            'open_trades' => [
                'limit' => $limits->isUnlimited('open_trades_max') ? null : $limits->get('open_trades_max'),
                'used' => $openForUser,
                'remaining' => $limits->remaining('open_trades_max', $openForUser),
            ],
            'trade_max_duration_seconds' => $limits->isUnlimited('trade_max_duration_seconds')
                ? null
                : $limits->get('trade_max_duration_seconds'),
        ];

        if ($tradingAccount) {
            $openForAccount = \App\Models\Trade::query()
                ->where('trading_account_id', $tradingAccount->id)
                ->where('status', 0)
                ->count();

            $context['open_trades_per_account'] = [
                'limit' => $limits->isUnlimited('open_trades_per_account_max') ? null : $limits->get('open_trades_per_account_max'),
                'used' => $openForAccount,
                'remaining' => $limits->remaining('open_trades_per_account_max', $openForAccount),
            ];
        }

        return $context;
    }
}
