<?php

namespace App\Http\Controllers\Admin;

use App\Models\AccountType;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRateLog;
use App\Services\LeadMax\CurrencyRates;
use App\Support\LeadMax\BrandWebsite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * LeadMax Settings (Figma 2524:20234 General, 2551:21745 Currency Management).
 * Values live in the company settings under "leadmax_general"; unset fields fall back to the tenant's site settings.
 */
class LeadMaxSettingsController extends LeadMaxController
{
    public const TABS = ['general' => 'General', 'currency' => 'Currency Management', 'integrations' => 'Integrations'];

    public const LANGUAGES = ['en' => 'English', 'ar' => 'Arabic', 'de' => 'German', 'es' => 'Spanish', 'fr' => 'French', 'hi' => 'Hindi', 'it' => 'Italian', 'nl' => 'Dutch', 'pt' => 'Portuguese', 'ru' => 'Russian', 'tr' => 'Turkish', 'zh-CN' => 'Chinese'];

    public const DATE_FORMATS = ['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'd.m.Y' => 'DD.MM.YYYY', 'M j, Y' => 'MMM D, YYYY'];

    public const TIME_FORMATS = ['H:i' => '24 Hour (HH:mm)', 'h:i A' => '12 Hour (hh:mm AM/PM)'];

    public const ACCOUNT_TYPES = ['customer' => 'Customer', 'lead' => 'Lead', 'demo' => 'Demo'];

    public const DISPLAY_FORMATS = ['symbol' => 'Symbol ($)', 'code' => 'Code (USD)', 'both' => 'Symbol & Code ($ USD)'];

    public const SEPARATORS = ['comma' => 'Comma (1,000.00)', 'period' => 'Period (1.000,00)', 'space' => 'Space (1 000.00)', 'none' => 'None (1000.00)'];

    public const FREQUENCIES = [1 => 'Every minute', 5 => 'Every 5 minutes', 15 => 'Every 15 minutes', 60 => 'Every hour', 1440 => 'Once a day'];

    public const ROUNDING = ['up' => 'Round up to nearest cent', 'nearest' => 'Round to nearest cent', 'down' => 'Round down to nearest cent'];

    /** Keys an exported settings file may carry back in. */
    public const GENERAL_KEYS = ['company_name', 'website', 'support_email', 'support_phone', 'timezone', 'language', 'country', 'state', 'city', 'postal_code', 'address', 'currency', 'lead_expiry_days', 'date_format', 'time_format', 'account_type', 'trading_group'];

    public function index(Request $request)
    {
        // "All settings tabs" off (Settings > Modules): only General — plus Integrations,
        // which carries the Google Drive connection and is not part of that bundle.
        $tabs = \App\Support\LeadMax\Features::enabled('leadmax_all_settings')
            ? self::TABS
            : array_intersect_key(self::TABS, ['general' => true, 'integrations' => true]);
        $tab = array_key_exists($request->input('tab'), $tabs) ? $request->input('tab') : 'general';

        return view('admin.leadmax.settings', [
            ...$this->common($request, 'settings'),
            'tab' => $tab,
            'tabs' => $tabs,
            'general' => $this->general(),
            'defaults' => $this->defaults(),
            'status' => $this->platformStatus(),
            'timezones' => $this->timezones(),
            'countries' => \App\Models\Country::query()->orderBy('name')->pluck('name'),
            'currencies' => Currency::query()->whereNotNull('code')->where('code', '!=', '')->orderByDesc('is_default')->orderBy('code')->get(['code', 'name', 'symbol']),
            ...($tab === 'currency' ? $this->currencyView($request) : []),
            ...($tab === 'integrations' ? $this->integrationsView() : []),
            'tradingGroups' => ['default' => 'Default', ...(\Illuminate\Support\Facades\Schema::hasTable('account_types') ? AccountType::query()->orderBy('name')->pluck('name', 'name')->all() : [])],
        ]);
    }

    public function saveGeneral(Request $request)
    {
        abort_unless(is_admin(), 403);
        // The brand website (the client area and WebTrader logo opens it): a bare domain gets https://;
        // what cannot be made an http(s) URL is kept as typed, so the rule below names the problem.
        if ($request->filled('website')) {
            $request->merge(['website' => BrandWebsite::normalize($request->input('website')) ?? $request->input('website')]);
        }
        $data = $request->validate([
            'company_name' => 'required|string|max:120',
            'website' => 'nullable|url:http,https|max:255',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:40',
            'timezone' => ['required', Rule::in(\DateTimeZone::listIdentifiers())],
            'language' => ['required', Rule::in(array_keys(self::LANGUAGES))],
            'country' => 'nullable|string|max:120',
            'state' => 'nullable|string|max:120',
            'city' => 'nullable|string|max:120',
            'postal_code' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'lead_expiry_days' => 'required|integer|min:0|max:3650',
            'date_format' => ['required', Rule::in(array_keys(self::DATE_FORMATS))],
            'time_format' => ['required', Rule::in(array_keys(self::TIME_FORMATS))],
            'account_type' => ['required', Rule::in(array_keys(self::ACCOUNT_TYPES))],
            'trading_group' => 'required|string|max:120',
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048',
            'logo_light' => 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048',
        ]);
        $values = collect($data)->except(['logo', 'logo_light'])->all();
        // "logo" is drawn on dark surfaces; "logo_light" is the variant for the light theme's white top bar.
        foreach (['logo', 'logo_light'] as $key) {
            if ($request->hasFile($key)) {
                $values[$key] = $request->file($key)->store('leadmax/logos', 'local');
                $this->deleteLogo($key);
            }
        }
        $this->store($values);

        return redirect()->route('admin.leadmax.settings')->with('success', 'Settings saved.');
    }

    /** The uploaded logo (?variant=light for the light-theme one), served from private storage. */
    public function logo(Request $request)
    {
        $path = $this->general()[$this->logoKey($request)] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=300', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function removeLogo(Request $request)
    {
        abort_unless(is_admin(), 403);
        $key = $this->logoKey($request);
        $this->deleteLogo($key);
        $this->store([$key => null]);

        return redirect()->route('admin.leadmax.settings')->with('success', 'Logo removed.');
    }

    private function logoKey(Request $request): string
    {
        return $request->input('variant') === 'light' ? 'logo_light' : 'logo';
    }

    public function clearCache()
    {
        abort_unless(is_admin(), 403);
        $company = Company::query()->findOrFail(app('company')->id);
        Company::clearCache($company);
        Cache::forget('leadmax:status:'.$company->id);

        return redirect()->route('admin.leadmax.settings')->with('success', 'Cache cleared.');
    }

    public function export()
    {
        abort_unless(is_admin(), 403);
        $payload = ['leadmax_settings' => 1, 'exported_at' => now()->toIso8601String(), 'general' => collect($this->general())->only(self::GENERAL_KEYS)->all(), 'currency' => $this->currency()];

        return response()->streamDownload(fn () => print (json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)), 'leadmax-settings-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }

    public function import(Request $request)
    {
        abort_unless(is_admin(), 403);
        $request->validate(['file' => 'required|file|max:512']);
        $payload = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);
        if (! is_array($payload) || ($payload['leadmax_settings'] ?? null) !== 1 || ! is_array($payload['general'] ?? null)) {
            return back()->withErrors(['file' => 'That file is not a LeadMax settings export.']);
        }
        $values = collect($payload['general'])->only(self::GENERAL_KEYS)->map(fn ($v) => is_scalar($v) || $v === null ? $v : null)->all();
        if (filled($values['website'] ?? null)) {
            $values['website'] = BrandWebsite::normalize($values['website']) ?? $values['website'];
        }
        $merged = [...$this->general(), ...$values];
        $validator = validator($merged, [
            'company_name' => 'required|string|max:120', 'website' => 'nullable|url:http,https|max:255', 'support_email' => 'nullable|email|max:255',
            'timezone' => ['required', Rule::in(\DateTimeZone::listIdentifiers())], 'language' => ['required', Rule::in(array_keys(self::LANGUAGES))],
            'lead_expiry_days' => 'required|integer|min:0|max:3650', 'date_format' => ['required', Rule::in(array_keys(self::DATE_FORMATS))],
            'time_format' => ['required', Rule::in(array_keys(self::TIME_FORMATS))], 'account_type' => ['required', Rule::in(array_keys(self::ACCOUNT_TYPES))],
        ]);
        if ($validator->fails()) {
            return back()->withErrors(['file' => 'Import failed: '.$validator->errors()->first()]);
        }
        $this->store($values);

        return redirect()->route('admin.leadmax.settings')->with('success', 'Settings imported.');
    }

    /** Reset to defaults: "general" keeps the logo, "all" also removes it. */
    public function reset(Request $request)
    {
        abort_unless(is_admin(), 403);
        $all = $request->input('scope') === 'all';
        if ($all) {
            $this->deleteLogo('logo');
            $this->deleteLogo('logo_light');
        }
        DB::transaction(function () use ($all) {
            $company = Company::query()->lockForUpdate()->findOrFail(app('company')->id);
            $settings = $company->settings ?? [];
            $settings['leadmax_general'] = $all ? [] : array_filter(['logo' => $settings['leadmax_general']['logo'] ?? null, 'logo_light' => $settings['leadmax_general']['logo_light'] ?? null]);
            if ($all) {
                $settings['leadmax_currency'] = [];
            }
            $this->persist($company, $settings);
        });

        return redirect()->route('admin.leadmax.settings')->with('success', $all ? 'All settings were reset.' : 'Settings restored to defaults.');
    }

    /* ---------------------------------------------------------------- Currency Management */

    public function saveCurrency(Request $request)
    {
        abort_unless(is_admin(), 403);
        $codes = $this->currencyCodes();
        $data = $request->validate([
            'base' => ['sometimes', Rule::in($codes)],
            'display' => ['sometimes', Rule::in(array_keys(self::DISPLAY_FORMATS))],
            'decimals' => 'sometimes|integer|min:0|max:6',
            'separator' => ['sometimes', Rule::in(array_keys(self::SEPARATORS))],
            'frequency' => ['sometimes', 'integer', Rule::in(array_keys(self::FREQUENCIES))],
            'fee_percent' => 'sometimes|numeric|min:0|max:100',
            'fee_min' => 'sometimes|numeric|min:0|max:1000000',
            'fee_max' => 'sometimes|numeric|min:0|max:1000000|gte:fee_min',
            'fee_rounding' => ['sometimes', Rule::in(array_keys(self::ROUNDING))],
        ]);
        if (isset($data['fee_max']) && ! isset($data['fee_min']) && $data['fee_max'] < $this->currency()['fee_min']) {
            return back()->withErrors(['fee_max' => 'The maximum fee must be at least the minimum fee.']);
        }
        $values = collect($data)->map(fn ($v, $k) => in_array($k, ['decimals', 'frequency'], true) ? (int) $v : (str_starts_with($k, 'fee_') && $k !== 'fee_rounding' ? (float) $v : $v))->all();
        if (isset($values['base'])) {
            $values['enabled'] = array_values(array_unique([...$this->enabledCodes(), $values['base']]));
        }
        $this->storeCurrency($values);

        return redirect()->route('admin.leadmax.settings', ['tab' => 'currency'])->with('success', 'Currency settings saved.');
    }

    public function toggleCurrency(string $code)
    {
        abort_unless(is_admin(), 403);
        abort_unless(in_array($code, $this->currencyCodes(), true), 404);
        if ($code === $this->currency()['base']) {
            return back()->withErrors(['currency' => 'The base currency cannot be disabled.']);
        }
        $enabled = $this->enabledCodes();
        $enabled = in_array($code, $enabled, true) ? array_values(array_diff($enabled, [$code])) : [...$enabled, $code];
        $this->storeCurrency(['enabled' => $enabled]);

        return redirect()->route('admin.leadmax.settings', ['tab' => 'currency', 'q' => request('q')])->with('success', $code.(in_array($code, $enabled, true) ? ' enabled.' : ' disabled.'));
    }

    public function addAlert(Request $request)
    {
        abort_unless(is_admin(), 403);
        $data = $request->validate(['code' => ['required', Rule::in($this->currencyCodes())], 'threshold' => 'required|numeric|min:0.1|max:100']);
        $alerts = collect($this->currency()['alerts'])->reject(fn ($a) => $a['code'] === $data['code'])->values()->all();
        $alerts[] = ['code' => $data['code'], 'threshold' => round((float) $data['threshold'], 2), 'enabled' => true];
        $this->storeCurrency(['alerts' => $alerts]);

        return redirect()->route('admin.leadmax.settings', ['tab' => 'currency'])->with('success', 'Alert rule added.');
    }

    public function toggleAlert(int $index)
    {
        abort_unless(is_admin(), 403);
        $alerts = $this->currency()['alerts'];
        abort_unless(isset($alerts[$index]), 404);
        $alerts[$index]['enabled'] = ! ($alerts[$index]['enabled'] ?? false);
        $this->storeCurrency(['alerts' => $alerts]);

        return redirect()->route('admin.leadmax.settings', ['tab' => 'currency']);
    }

    public function deleteAlert(int $index)
    {
        abort_unless(is_admin(), 403);
        $alerts = $this->currency()['alerts'];
        abort_unless(isset($alerts[$index]), 404);
        array_splice($alerts, $index, 1);
        $this->storeCurrency(['alerts' => $alerts]);

        return redirect()->route('admin.leadmax.settings', ['tab' => 'currency'])->with('success', 'Alert rule removed.');
    }

    public function testConnection(CurrencyRates $rates)
    {
        abort_unless(is_admin(), 403);
        $provider = $rates->provider();
        $message = match ($provider['state']) {
            'connected' => $provider['label'].' is connected (last price '.$this->ago($provider['age']).').',
            'delayed' => $provider['label'].' is reachable but prices are stale'.($provider['age'] !== null ? ' (last price '.$this->ago($provider['age']).')' : '').'.',
            default => 'No currency is linked to a price feed; rates are maintained manually.',
        };

        return redirect()->route('admin.leadmax.settings', ['tab' => 'currency'])->with($provider['state'] === 'connected' ? 'success' : 'warning', $message);
    }

    public function refreshRates(CurrencyRates $rates)
    {
        abort_unless(is_admin(), 403);
        $rates->refresh();

        return redirect()->route('admin.leadmax.settings', ['tab' => 'currency'])->with('success', 'Exchange rates refreshed.');
    }

    public function rateHistory()
    {
        abort_unless(is_admin(), 403);
        $codes = $this->enabledCodes();

        return response()->streamDownload(function () use ($codes) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Currency', 'Previous Rate (per USD)', 'New Rate (per USD)', 'Change %']);
            CurrencyRateLog::query()->whereIn('code', $codes)->latest('created_at')->limit(10000)->get()
                ->each(fn ($log) => fputcsv($out, [$log->created_at->toDateTimeString(), $log->code, $log->old_rate, $log->new_rate, $log->change_percent]));
            fclose($out);
        }, 'rate-history-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Saved currency settings over the defaults. */
    public function currency(): array
    {
        $defaults = [
            'base' => Currency::query()->where('is_default', true)->value('code') ?: 'USD',
            'display' => 'symbol', 'decimals' => 2, 'separator' => 'comma', 'frequency' => 5,
            'fee_percent' => 0.5, 'fee_min' => 1.0, 'fee_max' => 50.0, 'fee_rounding' => 'up',
            'enabled' => null, 'alerts' => [],
        ];

        return [...$defaults, ...array_filter(company_settings('leadmax_currency', []) ?: [], fn ($v) => $v !== null)];
    }

    /**
     * Settings > Integrations. Currently just Google Drive.
     *
     * The connection model is passed whole, but its tokens are $hidden and encrypted, so
     * nothing secret can reach the view by accident.
     */
    protected function integrationsView(): array
    {
        $drive = app(\App\Services\GoogleDrive\GoogleDriveService::class)->connectionFor(app('company')->id);
        $oauth = app(\App\Services\GoogleDrive\GoogleOAuth::class);

        return [
            'drive' => $drive,
            // Can this company start a connection at all — via its own client or the platform's.
            'driveConfigured' => $oauth->configuredFor($drive),
            'drivePlatformConfigured' => $oauth->platformConfigured(),
            'driveRedirectUri' => $oauth->redirectUri(),
        ];
    }

    protected function currencyView(Request $request): array
    {
        $settings = $this->currency();
        $all = Currency::query()->whereNotNull('code')->where('code', '!=', '')->orderByDesc('is_default')->orderBy('code')->get();
        $baseRate = (float) ($all->firstWhere('code', $settings['base'])?->rate ?: 1);
        $search = trim((string) $request->input('q'));
        $rows = $all->filter(fn ($c) => $search === '' || str_contains(mb_strtolower($c->code.' '.$c->name), mb_strtolower($search)))
            ->sortBy(fn ($c) => [$c->code === $settings['base'] ? 0 : 1, $c->code])->values();
        $enabled = $this->enabledCodes();

        return [
            'cur' => $settings,
            'currencyRows' => $rows->map(fn ($c) => [
                'code' => $c->code, 'name' => $c->name, 'symbol' => $c->symbol, 'flag' => $this->currencyFlag($c->code),
                'rate' => $c->code === $settings['base'] ? 1.0 : CurrencyRates::convert((float) $c->rate, $baseRate), 'enabled' => in_array($c->code, $enabled, true),
                'updated' => $c->updated_at ? $this->ago((int) $c->updated_at->diffInSeconds(now(), true)) : '—', 'base' => $c->code === $settings['base'],
            ]),
            'allCurrencies' => $all,
            'provider' => app(CurrencyRates::class)->provider(),
            'audit' => $request->input('panel') === 'audit' ? CurrencyRateLog::query()->latest('created_at')->latest('id')->limit(50)->get() : null,
        ];
    }

    protected function currencyCodes(): array
    {
        return Currency::query()->whereNotNull('code')->where('code', '!=', '')->pluck('code')->all();
    }

    /** Enabled codes: every currency until the tenant chooses otherwise. */
    protected function enabledCodes(): array
    {
        $enabled = $this->currency()['enabled'];

        return is_array($enabled) ? array_values(array_intersect($enabled, $this->currencyCodes())) : $this->currencyCodes();
    }

    /** Flag for a fiat currency: its issuing country (first two letters of the ISO code), EUR -> EU; none for crypto and metals. */
    protected function currencyFlag(string $code): ?string
    {
        if (in_array($code, ['BTC', 'ETH', 'USDT', 'USDC', 'LTC', 'XRP', 'BNB', 'SOL', 'DOGE', 'TRX', 'ADA', 'XAU', 'XAG'], true)) {
            return null;
        }
        $iso = ['EUR' => 'eu', 'XAF' => 'cm', 'XOF' => 'sn', 'ANG' => 'cw', 'XCD' => 'ag'][$code] ?? strtolower(substr($code, 0, 2));

        return strlen($code) === 3 && file_exists(public_path('images/flags/'.$iso.'.svg')) ? asset('images/flags/'.$iso.'.svg') : null;
    }

    protected function ago(?int $seconds): string
    {
        return match (true) {
            $seconds === null => 'unknown',
            $seconds < 60 => 'just now',
            $seconds < 3600 => intdiv($seconds, 60).' min ago',
            $seconds < 86400 => intdiv($seconds, 3600).' hr ago',
            default => intdiv($seconds, 86400).' days ago',
        };
    }

    private function storeCurrency(array $values): void
    {
        DB::transaction(function () use ($values) {
            $company = Company::query()->lockForUpdate()->findOrFail(app('company')->id);
            $settings = $company->settings ?? [];
            $settings['leadmax_currency'] = [...($settings['leadmax_currency'] ?? []), ...$values];
            $this->persist($company, $settings);
        });
    }

    /** Saved values over the defaults. */
    public function general(): array
    {
        $general = [...$this->defaults(), ...array_filter(company_settings('leadmax_general', []) ?: [], fn ($v) => $v !== null)];
        // Website is the brand website, one value shared with the superadmin's tenant Modules.
        $general['website'] = company_settings('brand_website_url') ?: $general['website'];

        return $general;
    }

    /** Defaults come from the tenant's existing site settings. */
    protected function defaults(): array
    {
        $company = app('company');

        return [
            'company_name' => company_settings('site_name', $company->name ?? config('app.name')),
            'website' => company_settings('site_url', ''),
            'support_email' => company_settings('support_email', $company->email ?? ''),
            'support_phone' => company_settings('contact_phone', $company->phone ?? ''),
            'timezone' => config('app.timezone'),
            'language' => 'en',
            'country' => '', 'state' => '', 'city' => '', 'postal_code' => '',
            'address' => (string) ($company->address ?? ''),
            'currency' => Currency::query()->where('is_default', true)->value('code') ?: 'USD',
            'lead_expiry_days' => 30,
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'account_type' => 'customer',
            'trading_group' => 'default',
            'logo' => null,
            'logo_light' => null,
        ];
    }

    /** "(GMT+3) Jerusalem" labels keyed by identifier, ordered by offset. */
    protected function timezones(): array
    {
        $now = now();

        return collect(\DateTimeZone::listIdentifiers())
            ->map(function ($id) use ($now) {
                $offset = (new \DateTimeZone($id))->getOffset($now);
                $hours = intdiv(abs($offset), 3600);
                $minutes = intdiv(abs($offset) % 3600, 60);
                $gmt = 'GMT'.($offset === 0 ? '' : ($offset > 0 ? '+' : '-').$hours.($minutes ? ':'.str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : ''));

                return ['id' => $id, 'offset' => $offset, 'label' => '('.$gmt.') '.str_replace('_', ' ', last(explode('/', $id)))];
            })
            ->sortBy([['offset', 'asc'], ['label', 'asc']])
            ->mapWithKeys(fn ($t) => [$t['id'] => $t['label']])
            ->all();
    }

    /** Database, cache and storage health, cached for a minute. */
    protected function platformStatus(): array
    {
        return Cache::remember('leadmax:status:'.app('company')->id, 60, function () {
            $checks = [
                'database' => rescue(fn () => DB::select('select 1') !== null, false, false),
                'cache' => rescue(fn () => Cache::add('leadmax:ping', 1, 5) || Cache::has('leadmax:ping'), false, false),
                'storage' => rescue(fn () => Storage::disk('local')->put('leadmax/.health', now()->toIso8601String()), false, false),
            ];
            $failed = array_keys(array_filter($checks, fn ($ok) => ! $ok));

            return $failed === []
                ? ['ok' => true, 'title' => 'All Systems Operational', 'note' => 'Your platform is running smoothly.']
                : ['ok' => false, 'title' => 'Degraded Performance', 'note' => 'Check failed: '.implode(', ', $failed).'.'];
        });
    }

    private function store(array $values): void
    {
        DB::transaction(function () use ($values) {
            $company = Company::query()->lockForUpdate()->findOrFail(app('company')->id);
            $settings = $company->settings ?? [];
            // The Website is stored once, as the brand website the superadmin's tenant Modules edits
            // too, so the two screens can never disagree; an older copy here is dropped.
            if (array_key_exists('website', $values)) {
                $settings['brand_website_url'] = BrandWebsite::normalize($values['website']);
                unset($values['website'], $settings['leadmax_general']['website']);
            }
            $settings['leadmax_general'] = [...($settings['leadmax_general'] ?? []), ...$values];
            $this->persist($company, $settings);
        });
    }

    private function persist(Company $company, array $settings): void
    {
        $company->forceFill(['settings' => $settings])->save();
        Company::clearCache($company->fresh());
        app()->instance('company', $company->fresh());
    }

    private function deleteLogo(string $key = 'logo'): void
    {
        $path = company_settings('leadmax_general', [])[$key] ?? null;
        if ($path && str_starts_with($path, 'leadmax/logos/')) {
            Storage::disk('local')->delete($path);
        }
    }
}
