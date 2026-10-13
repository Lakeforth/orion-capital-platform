<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledTrade extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'user_id',
        'asset_id',
        'trade_id',
        'direction',
        'duration',
        'profit_target',
        'volume',
        'leverage',
        'margin',
        'scheduled_at',
        'activated_at',
        'completed_at',
        'cancelled_at',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'duration' => 'integer',
        'profit_target' => 'float',
        'volume' => 'decimal:2',
        'leverage' => 'integer',
        'margin' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }
}
