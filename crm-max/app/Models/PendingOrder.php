<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A web trader limit/stop order waiting for its price. It reserves no margin and is invisible
 * to every balance and trade list until it triggers and becomes a normal Trade.
 */
class PendingOrder extends Model
{
    use BelongsToCompany, HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_TRIGGERED = 'triggered';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id',
        'user_id',
        'trading_account_id',
        'asset_id',
        'trade_id',
        'trade_type',
        'order_type',
        'pending_price',
        'volume',
        'leverage',
        'lot',
        'margin',
        'take_profit',
        'stop_loss',
        'payload',
        'status',
        'cancel_reason',
        'opened_trade_id',
        'triggered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'pending_price' => 'float',
        'volume' => 'float',
        'margin' => 'float',
        'take_profit' => 'float',
        'stop_loss' => 'float',
        'payload' => 'array',
        'triggered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
