<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChallengePlan extends Model
{
    use BelongsToCompany, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'account_size',
        'price',
        'currency',
        'profit_split_pct',
        'refund_fee_on_funded',
        'is_active',
        'archived_at',
        'free_trial_enabled',
        'sort_order',
        'scaling_enabled',
        'scaling_min_profit_pct',
        'scaling_min_trading_days',
        'scaling_required_months',
        'scaling_multiplier',
    ];

    protected $casts = [
        'account_size' => 'float',
        'price' => 'float',
        'profit_split_pct' => 'float',
        'refund_fee_on_funded' => 'boolean',
        'is_active' => 'boolean',
        'archived_at' => 'datetime',
        'free_trial_enabled' => 'boolean',
        'scaling_enabled' => 'boolean',
        'scaling_min_profit_pct' => 'float',
        'scaling_min_trading_days' => 'integer',
        'scaling_required_months' => 'integer',
        'scaling_multiplier' => 'float',
    ];

    public function phases(): HasMany
    {
        return $this->hasMany(ChallengePlanPhase::class)->orderBy('phase_number');
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<self>  $query */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<self>  $query */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }
}
