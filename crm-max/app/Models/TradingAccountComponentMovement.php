<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradingAccountComponentMovement extends Model
{
    use HasUuids;

    public const COMPONENT_DEPOSIT = 'deposit';

    public const COMPONENT_BONUS = 'bonus';

    public const COMPONENT_CREDIT = 'credit';

    public const COMPONENT_PROFIT = 'profit';

    public const SOURCE_TRADE_SETTLEMENT = 'trade_settlement';

    public const SOURCE_ADMIN_FUNDING = 'admin_funding';

    public const SOURCE_CLIENT_DEPOSIT = 'client_deposit';

    public const SOURCE_CLIENT_WITHDRAWAL = 'client_withdrawal';

    public const SOURCE_SPOT_WALLET_TRANSFER = 'spot_wallet_transfer';

    public const SOURCE_BACKFILL = 'backfill';

    public $timestamps = false;

    protected $fillable = [
        'trading_account_id',
        'component',
        'delta',
        'balance_after',
        'source',
        'transaction_id',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'delta' => 'float',
        'balance_after' => 'float',
        'created_at' => 'datetime',
    ];

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class)->withTrashed();
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
