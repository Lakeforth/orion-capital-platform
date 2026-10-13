<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeDailyStat extends Model
{
    use HasUuids;

    protected $fillable = [
        'challenge_id',
        'date',
        'starting_equity',
        'lowest_equity',
        'highest_equity',
        'closing_equity',
        'daily_pnl',
        'trades_count',
    ];

    protected $casts = [
        'date' => 'date',
        'starting_equity' => 'float',
        'lowest_equity' => 'float',
        'highest_equity' => 'float',
        'closing_equity' => 'float',
        'daily_pnl' => 'float',
        'trades_count' => 'integer',
    ];

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
