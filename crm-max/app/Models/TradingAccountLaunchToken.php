<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TradingAccountLaunchToken extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = [
        'company_id',
        'trading_account_id',
        'token',
        'abilities',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'abilities' => 'array',
    ];

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }

    /**
     * Issue a fresh single-use launch token for this account and return the
     * RAW token (only ever available here -- the stored column is a hash).
     */
    public static function issueFor(TradingAccount $tradingAccount, int $ttlSeconds = 45, ?array $abilities = null): array
    {
        $rawToken = Str::random(64);

        $launchToken = static::create([
            'company_id' => $tradingAccount->company_id,
            'trading_account_id' => $tradingAccount->id,
            'token' => hash('sha256', $rawToken),
            'abilities' => $abilities,
            'expires_at' => now()->addSeconds($ttlSeconds),
        ]);

        return [$launchToken, $rawToken];
    }

    public static function findValidByRawToken(string $rawToken): ?self
    {
        return static::withoutGlobalScope('company')
            ->where('token', hash('sha256', $rawToken))
            ->first();
    }

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    /**
     * Atomically mark this token used, guarding against two concurrent
     * requests both passing isValid() before either writes.
     */
    public function consume(): bool
    {
        return DB::transaction(function () {
            $locked = static::query()->whereKey($this->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isValid()) {
                return false;
            }

            $locked->update(['used_at' => now()]);
            $this->used_at = $locked->used_at;

            return true;
        });
    }
}
