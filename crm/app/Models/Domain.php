<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'domain', 'website_domain',
        'status', 'is_primary', 'is_verified', 'is_active',
        'verification_token',
        'domain_record_id',
        'cert_id', 'full_cert_id',
        'server_id', 'site_id',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'platform_id',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function ($domain) {
            // Clear domain cache
            Cache::forget("domain:{$domain->domain}");

            // Clear company domains cache
            Cache::forget("company:{$domain->company_id}:domains");

            // Clear other company caches that might be affected
            $company = $domain->company;
            if ($company) {
                Company::clearCache($company);
            }
        });

        static::deleted(function ($domain) {
            // Clear domain cache
            Cache::forget("domain:{$domain->domain}");

            // Clear company domains cache
            Cache::forget("company:{$domain->company_id}:domains");

            // Clear other company caches
            $company = $domain->company;
            if ($company) {
                Company::clearCache($company);
            }
        });

        static::updating(function ($domain) {
            if ($domain->isDirty('status')) {
                $newStatus = $domain->status;
                if ($newStatus === 'ssl_initiated') {
                    //                    CheckDomainCertStatus::dispatch($domain);
                }
            }
        });
    }

    public static function findByHostname(string $hostname)
    {
        return Cache::remember("domain:{$hostname}", now()->addDay(), function () use ($hostname) {
            return self::where('domain', $hostname)
                ->where('is_active', true)
                ->first();
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get company with caching
     */
    public function getCachedCompany()
    {
        return Company::findCached($this->company_id);
    }

    // Generate verification token
    public static function generateVerificationToken(): string
    {
        return \Illuminate\Support\Str::random(64);
    }

    // Get full domain with protocol
    public function getFullDomainUrl(): string
    {
        return 'https://'.$this->domain;
    }

    protected function siteId(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) ($this->attributes['site_id'] ?? config('app.site_id')),
        );
    }

    protected function customBaseDomain(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) config('app.custom_domain'),
        );
    }

    protected function platformId(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->company_id,
        );
    }

    public function serverId(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) ($this->attributes['server_id'] ?? config('app.server_id')),
        );
    }

    protected function serverInfo(): Attribute
    {
        return Attribute::make(
            get: function (): ?array {
                $serverId = (string) ($this->attributes['server_id'] ?? '');
                $servers = config('forge.servers', []);

                foreach ($servers as $server) {
                    if ((string) ($server['id'] ?? '') === $serverId) {
                        return [
                            'label' => $server['label'] ?? $server['name'] ?? $server['id'] ?? 'Server',
                            'name' => $server['name'] ?? $server['label'] ?? $server['id'] ?? 'Server',
                            'ip' => $server['ip'] ?? null,
                        ];
                    }
                }

                return null;
            }
        );
    }
}
