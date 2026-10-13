<?php

namespace App\Models;

use App\Jobs\SendTelegramActivityNotification;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trade extends Model
{
    use BelongsToCompany, HasUuids, SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'trading_account_id',
        'asset_id',
        'trade_type',
        'result',
        'status',
        'duration',
        'leverage',
        'traded_amount',
        'opening_price',
        'closing_price',
        'profit',
        'take_profit',
        'stop_loss',
        'is_take_profit',
        'is_stop_loss',
        'by_admin',
        'base_rate',
        'opened_at',
        'close_at',
        'close_reason',
        'volume',
        'margin',
        'lot',
        'trade_id',
        'created_at',
    ];

    protected $casts = [
        'status' => 'integer',
        'result' => 'integer',
        'traded_amount' => 'float',
        'leverage' => 'integer',
        'profit' => 'float',
        'take_profit' => 'float',
        'base_rate' => 'float',
        'stop_loss' => 'float',
        'paid_com' => 'float',
        'opening_price' => 'float',
        'closing_price' => 'float',
        'volume' => 'decimal:2',
        'margin' => 'decimal:2',

        'is_stop_loss' => 'boolean',
        'practice' => 'boolean',
        'by_admin' => 'boolean',
        'is_take_profit' => 'boolean',
        'opened_at' => 'datetime',
        'close_at' => 'datetime',
    ];

    protected array $dates = ['created_at'];

    protected $with = ['asset', 'tradingAccount'];

    // volume_lots added here (rather than only being a Blade-accessible
    // accessor) so it also serializes into JSON API responses -- the admin
    // "Open Positions" table (BackendController::getAdminTrades()) consumes
    // this endpoint directly as JSON and was displaying the raw, unconverted
    // `volume` (contract units, not lots), disagreeing with every other
    // admin trade table and with what the client's own platform shows for
    // the same trade.
    protected $appends = ['end_time', 'qty', 'open_at', 'amount', 'volume_lots'];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    public function tradingAccount()
    {
        return $this->belongsTo(TradingAccount::class)->withTrashed();
    }

    public function currency()
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    //    public function getProfitAttribute($value)
    //    {
    //        return $value - $this->paid_com;
    //    }

    public function getAmountAttribute(): float|int
    {
        if ($this->base_rate) {
            return $this->traded_amount * $this->leverage;
        } else {
            return $this->traded_amount * $this->leverage;
        }
    }

    public function getOpenAtAttribute(): string
    {
        if ($this->created_at) {
            return Carbon::parse($this->created_at)->format('d-m-Y H:i:s');
        } else {
            return Carbon::parse($this->updated_at)->format('d-m-Y');
        }
    }

    public function getOpenedAtAttribute($value): string
    {
        if (! $value) {
            return Carbon::parse($this->created_at)->format('d-m-Y H:i:s');
        } else {
            return Carbon::parse($value)->format('d-m-Y H:i:s');
        }
    }

    public function getCloseAtAttribute($value): ?string
    {
        if ($value) {
            return Carbon::parse($value)->format('d-m-Y H:i:s');
        } else {
            return null;
        }
    }

    /**
     * Same conversion TradeResource exposes to trader-facing UIs (trade room,
     * client area) -- the raw `volume` column is stored in contract units,
     * not lots, so admin views showing it unconverted disagree with what the
     * customer actually sees for the same trade.
     */
    public function getVolumeLotsAttribute(): float
    {
        return $this->asset
            ? round(((float) $this->volume) / asset_contract_size($this->asset), 2)
            : (float) $this->volume;
    }

    public function getQtyAttribute(): float
    {
        if ($this->opening_price > 0) {
            if ($this->base_rate) {
                return ($this->traded_amount * (float) $this->leverage) / $this->base_rate;
                //                return (float)number_format($this->traded_amount / $this->base_rate,2);
            }
            $qty = ($this->traded_amount * (float) $this->leverage) / $this->opening_price;

            return round($qty, 3);
        } else {
            return 0.000;
        }
    }

    public function getCreatedAtAttribute($val)
    {
        if (! $val) {
            return $this->updated_at;
        }

        return $val;
    }

    public function getEndTimeAttribute(): Carbon
    {
        if ($this->created_at) {
            return Carbon::parse($this->created_at)->addSeconds($this->duration);
        } else {
            return Carbon::parse($this->updated_at)->addSeconds($this->duration);
        }
    }

    protected static function boot()
    {
        parent::boot();

        // Auto-set trade_date to created_at when creating a new record
        static::creating(function ($model) {
            $model->opened_at = carbon::now();
        });

        static::created(function (Trade $trade) {
            SendTelegramActivityNotification::dispatch(
                'trade_opened',
                $trade->id,
                auth()->id()
            );
        });

        static::updated(function (Trade $trade) {
            $wasOpen = (int) $trade->getOriginal('status') === 0;
            $isClosed = (int) $trade->status === 1;

            if ($wasOpen && $isClosed) {
                SendTelegramActivityNotification::dispatch(
                    'trade_closed',
                    $trade->id,
                    auth()->id()
                );
            }
        });
    }
}
