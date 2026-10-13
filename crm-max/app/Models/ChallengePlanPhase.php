<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengePlanPhase extends Model
{
    use HasUuids;

    protected $fillable = [
        'challenge_plan_id',
        'phase_number',
        'name',
        'profit_target_pct',
        'max_daily_loss_pct',
        'max_total_drawdown_pct',
        'drawdown_type',
        'max_lot_size',
        'allow_weekend_holding',
        'min_trading_days',
        'max_duration_days',
    ];

    protected $casts = [
        'phase_number' => 'integer',
        'profit_target_pct' => 'float',
        'max_daily_loss_pct' => 'float',
        'max_total_drawdown_pct' => 'float',
        'max_lot_size' => 'float',
        'allow_weekend_holding' => 'boolean',
        'min_trading_days' => 'integer',
        'max_duration_days' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ChallengePlan::class, 'challenge_plan_id');
    }
}
