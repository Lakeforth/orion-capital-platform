<?php

namespace App\Services\LeadMax;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Currency;
use App\Models\CurrencyRateLog;
use App\Models\User;
use App\Notifications\AccountNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Exchange rates for LeadMax Settings > Currency Management. Rates live in the shared currencies table
 * (units per 1 USD, refreshed from the asset price feed by app:update-currency); this keeps a throttled
 * history of them and raises each tenant's rate alerts.
 */
class CurrencyRates
{
    /** Log a rate at most hourly unless it moved this much (percent) since the last entry. */
    public const LOG_MOVE = 0.5;

    public const ALERT_COOLDOWN_HOURS = 6;

    public function record(Currency $currency): ?CurrencyRateLog
    {
        if (! $currency->code || ! Schema::hasTable('currency_rate_logs')) {
            return null;
        }
        $rate = (float) $currency->rate;
        $last = CurrencyRateLog::query()->where('code', $currency->code)->latest('created_at')->latest('id')->first();
        $change = $last && $last->new_rate > 0 ? ($rate - $last->new_rate) / $last->new_rate * 100 : 0.0;
        if ($last && (abs($change) < self::LOG_MOVE && $last->created_at->gt(now()->subHour()) || $rate === $last->new_rate)) {
            return null;
        }
        $log = CurrencyRateLog::create(['code' => $currency->code, 'old_rate' => $last?->new_rate, 'new_rate' => $rate, 'change_percent' => round($change, 4), 'created_at' => now()]);
        $this->checkAlerts($currency->code, $rate);

        return $log;
    }

    /** Notify the admins of every LeadMax tenant whose alert for this currency was crossed over the last 24 hours. */
    public function checkAlerts(string $code, float $rate): void
    {
        $dayAgo = CurrencyRateLog::query()->where('code', $code)->where('created_at', '>=', now()->subDay())->oldest('created_at')->value('new_rate');
        if (! $dayAgo || $dayAgo <= 0) {
            return;
        }
        $move = ($rate - $dayAgo) / $dayAgo * 100;
        $companies = Company::query()->where('settings', 'like', '%leadmax_currency%')->get(['id', 'settings']);
        foreach ($companies as $company) {
            $settings = $company->settings['leadmax_currency'] ?? [];
            $base = $settings['base'] ?? 'USD';
            foreach ($settings['alerts'] ?? [] as $alert) {
                if (($alert['code'] ?? null) !== $code || ! ($alert['enabled'] ?? false) || abs($move) < (float) ($alert['threshold'] ?? 0)) {
                    continue;
                }
                if (! Cache::add('leadmax:rate-alert:'.$company->id.':'.$code, 1, now()->addHours(self::ALERT_COOLDOWN_HOURS))) {
                    continue;
                }
                $admins = User::query()->where('company_id', $company->id)->whereHasRole(['admin', 'tenant_admin'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new AccountNotification([
                        'title' => 'Rate alert: '.$code.'/'.$base,
                        'message' => sprintf('%s/%s moved %s%.2f%% in 24 hours (alert at ±%s%%).', $code, $base, $move >= 0 ? '+' : '-', abs($move), $alert['threshold']),
                        'type' => 'rate_alert', 'color' => 'warning',
                    ]));
                }
            }
        }
    }

    /** Run the platform refresh now (the same command the scheduler runs every minute). */
    public function refresh(): int
    {
        return Artisan::call('app:update-currency');
    }

    /** Feed health: ['label' => ..., 'state' => connected|delayed|manual, 'age' => seconds|null]. */
    public function provider(): array
    {
        $keys = Currency::query()->whereNotNull('asset_key')->where('asset_key', '!=', '')->pluck('asset_key');
        if ($keys->isEmpty()) {
            return ['label' => 'Manual Rates', 'state' => 'manual', 'age' => null];
        }
        $assets = Asset::query()->whereIn('sym_key', $keys)->get(['id', 'sym_key', 'price_updated_at', 'updated_at']);
        $age = $assets->map->price_age_seconds->filter(fn ($s) => $s !== null)->min();

        return ['label' => 'Platform Price Feed', 'state' => $age !== null && $age <= 900 ? 'connected' : 'delayed', 'age' => $age];
    }

    /** Rate in units per 1 USD re-based on another currency. */
    public static function convert(float $rate, float $baseRate): float
    {
        return $baseRate > 0 ? $rate / $baseRate : $rate;
    }
}
