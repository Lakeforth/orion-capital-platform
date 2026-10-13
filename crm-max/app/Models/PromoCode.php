<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCode extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = [
        'company_id',
        'user_id',
        'challenge_id',
        'code',
        'discount_pct',
        'is_active',
        'expires_at',
        'max_uses',
        'times_used',
        'source',
    ];

    protected $casts = [
        'discount_pct' => 'float',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'times_used' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function isRedeemableBy(string $userId): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->times_used >= $this->max_uses) {
            return false;
        }

        if ($this->user_id && $this->user_id !== $userId) {
            return false;
        }

        return true;
    }
}
