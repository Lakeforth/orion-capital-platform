<?php

namespace App\Models;

use App\Jobs\CompanyCreatedJob;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Company extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'owner_id',
        'name', 'slug', 'email', 'phone', 'address',
        'mail_host', 'mail_port', 'mail_username', 'mail_password',
        'mail_encryption', 'mail_from_address', 'mail_from_name', 'subscription_type',
        'is_active', 'settings', 'enable_subscription', 'subscription_valid_till', 'subscription_type',
        'type', 'plan_id',

    ];

    protected $casts = [
        'settings' => 'array',
        'enable_subscription' => 'boolean',
        'subscription_valid_till' => 'date',
    ];

    protected $appends = [
        'urls',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Clear the cache when a company is updated
        static::updated(function ($company) {
            self::clearCache($company);
        });

        // Clear the cache when a company is deleted
        static::deleted(function ($company) {
            self::clearCache($company);
        });

        static::created(function (Company $company) {

            CompanyCreatedJob::dispatch($company->id);

        });
    }

    /**
     * Clear all caches related to this company
     */
    public static function clearCache($company): void
    {
        $keys = [
            "company:{$company->id}",
            "company:slug:{$company->slug}",
            "company:{$company->id}:domains",
        ];

        $company->loadMissing('domains');

        foreach ($company->domains as $domain) {
            $keys[] = "domain:{$domain->domain}";
        }

        // Always clear via the app cache store first. Staging/other envs may use a
        // CACHE_PREFIX other than main/custom, and Redis may be reachable even when
        // CACHE_STORE is database/file — skipping Cache::forget left tenants on
        // stale settings (e.g. bitrace_enabled) for up to an hour.
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        self::bustCacheViaRedis($keys);
    }

    /**
     * Also bust sibling deployment prefixes in Redis (your-platform main/custom, plus the
     * current app prefix). Best-effort — failures are ignored because Cache::forget
     * already cleared this app's store above.
     */
    private static function bustCacheViaRedis(array $keys): void
    {
        $client = config('database.redis.client', 'phpredis');

        if ($client === 'phpredis' && ! extension_loaded('redis')) {
            return;
        }

        try {
            $redis = Redis::connection('cache');
            $prefixes = array_values(array_unique(array_filter([
                'main',
                'custom',
                (string) config('cache.prefix'),
            ])));

            foreach ($prefixes as $cachePrefix) {
                foreach ($keys as $key) {
                    $redis->del("{$cachePrefix}{$key}");
                }
            }
        } catch (\Throwable) {
            // ignore — primary clear already happened via Cache::forget
        }
    }

    /**
     * Find a company by slug with caching
     */
    public static function findBySlug(string $slug)
    {
        return Cache::remember("company:slug:{$slug}", now()->addHour(), function () use ($slug) {
            return self::where('slug', $slug)->where('is_active', true)->first();
        });
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => str_replace('://', '://'.$this->slug.'.', config('app.url'))
        );
    }

    protected function planTitle(): Attribute
    {
        return Attribute::get(fn () => $this->plan?->title);
    }

    protected function urls(): Attribute
    {
        return Attribute::get(function (): array {
            $scheme = config('platform.url_scheme', 'https');
            $domain = config('platform.url_domain', config('app.domain'));
            $slug = $this->slug;

            return collect(config('platform.urls', []))
                ->map(fn (array $config) => [
                    'title' => $config['title'],
                    'url' => sprintf(
                        '%s://%s-%s.%s',
                        $scheme,
                        $config['prefix'],
                        $slug,
                        $domain
                    ),
                ])
                ->all();
        });
    }

    /**
     * Find a company by ID with caching
     */
    public static function findCached($id)
    {
        return Cache::remember("company:{$id}", now()->addHour(), function () use ($id) {
            return self::find($id);
        });
    }

    /**
     * Safely return settings as an array.
     */
    public function settingsArray(): array
    {
        $settings = $this->settings;

        if (is_string($settings)) {
            $settings = json_decode($settings, true) ?? [];
        }

        return is_array($settings) ? $settings : [];
    }

    /**
     * Get a specific setting value.
     */
    public function setting(string $key, $default = null)
    {
        return $this->settingsArray()[$key] ?? $default;
    }

    /**
     * Get a config value with sensible defaults.
     */
    public function config(string $key, $default = null)
    {
        $value = $this->setting($key, $default);

        if ($key === 'telegram_chat_id' && blank($value)) {
            return config('app.telegram_chat_id');
        }

        return $value;
    }

    public function telegramChatIdForDepartment(string $department = 'general'): ?string
    {
        $legacyChatId = $this->config('telegram_chat_id');
        $generalChatId = $this->setting('telegram_general_department');
        $financeChatId = $this->setting('telegram_finance_department');

        return match ($department) {
            'finance' => $financeChatId ?: $generalChatId ?: $legacyChatId,
            default => $generalChatId ?: $legacyChatId,
        };
    }

    public function savedTelegramChatIds(): array
    {
        return array_values(array_unique(array_filter([
            $this->setting('telegram_general_department'),
            $this->setting('telegram_finance_department'),
            $this->setting('telegram_chat_id'),
        ], fn ($value) => filled($value))));
    }

    public function hasSavedTelegramChatId(string $chatId): bool
    {
        return in_array($chatId, $this->savedTelegramChatIds(), true);
    }

    public function telegramNotificationsEnabled(): bool
    {
        return $this->telegramDepartmentNotificationsEnabled('general');
    }

    public function telegramDepartmentNotificationsEnabled(string $department = 'general'): bool
    {
        $settings = $this->settingsArray();
        $departmentSetting = match ($department) {
            'finance' => 'enable_telegram_finance_notification',
            default => 'enable_telegram_general_notification',
        };

        if (array_key_exists($departmentSetting, $settings)) {
            return ($settings[$departmentSetting] ?? 'no') === 'yes';
        }

        return ($settings['enable_telegram_notification'] ?? 'no') === 'yes';
    }

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function assets(): HasOne
    {
        return $this->hasOne(CompanyAsset::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(User::class)
            ->whereHasRole('customer');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(User::class)
            ->whereHasRole('lead');
    }

    public function deleteWithUsers(): void
    {
        DB::transaction(function (): void {
            $this->users()->get()->each(function (User $user): void {
                $user->delete();
            });

            $this->delete();
        });
    }

    public function admins(): HasMany
    {
        return $this->hasMany(User::class)
            ->whereHasRole('admin');
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function user_ids(): HasMany
    {
        return $this->hasMany(UserId::class);
    }

    /**
     * Get all domains for this company with caching
     */
    public function getCachedDomains()
    {
        return Cache::remember("company:{$this->id}:domains", now()->addHour(), function () {
            return $this->domains()->where('is_verified', true)->get();
        });
    }

    /**
     * Get the primary domain with caching
     */
    public function getPrimaryDomain()
    {
        $domains = $this->getCachedDomains();

        return $domains->firstWhere('is_primary', true);
    }
}
