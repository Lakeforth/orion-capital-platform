<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'user_id',
        'limit_pay_day_transfer',
        'credit_limit',
        'balance',
        'reserved',
        'status',
//        'transaction_status',
    ];

    protected $with = ['user'];

    protected $appends = ['bal','bal_val','balance_val'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }


    public function lastDeposit()
    {
        return $this->hasOne(Transaction::class, 'account_id')
            ->where('type', 'd')
            ->latest();
    }

    public function credit_wallet()
    {
        return $this->where('title', 'credit_wallet')->first();
    }

    public function bonus_wallet()
    {
        return $this->where('title', 'bonus_wallet')->first();
    }



    public function lastWithdraw()
    {
        return $this->hasOne(Transaction::class, 'account_id')
            ->where('type', 'w')
            ->latest();
    }




    public function getBalAttribute($amount)
    {
        return $this->balance;
//        return $this->balance - $this->reserved;
    }


    public function getBalValAttribute($amount): string
    {
        return format_price($this->bal,$this->currency_id);
    }
    public function getBalanceValAttribute($amount): string
    {
        return format_price($this->balance,$this->currency_id);
    }

    public function deposit($amount)
    {
        $this->balance += $amount;
        $this->save();
    }

    public function withdraw($amount)
    {
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
            $this->save();
//            return true; // Withdrawal successful
        }

//        return false; // Insufficient balance for withdrawal
    }

    /**
     * Credit line semantics, meaningful only for title=='credit_wallet' rows.
     * balance is <= 0 here -- it's the drawn amount, not a normal wallet
     * balance -- so available is what's left of the limit.
     */
    public function getAvailableAttribute(): float
    {
        return (float) ($this->credit_limit ?? 0) + (float) $this->balance;
    }

    public function draw(float $amount): bool
    {
        if ($this->available < $amount) {
            return false;
        }

        $this->balance -= $amount;

        return $this->save();
    }

    public function repay(float $amount): void
    {
        $this->balance = min(0, $this->balance + $amount);
        $this->save();
    }
}
