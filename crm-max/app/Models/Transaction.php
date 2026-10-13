<?php

namespace App\Models;

use App\Jobs\SendTelegramActivityNotification;
use App\Services\EmailService;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Transaction extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'order_id',
        'user_id',
        'type',
        'amount',
        'details',
        'initializer_name',
        'initializer_phone',
        'reason',
        'account',
        'created_at', 'method', 'status',
    ];

    protected $appends = ['t_type', 'date', 'time'];

    protected $casts = [
        'details' => 'array',
        'order_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if ($transaction->isDeposit() && ! $transaction->order_id) {
                $transaction->order_id = self::nextOrderId();
            }
        });

        static::created(function (Transaction $transaction) {
            $performedBy = auth()->id();

            if ($transaction->isDeposit()) {
                SendTelegramActivityNotification::dispatch(
                    'transaction_deposit',
                    $transaction->id,
                    $performedBy
                );
            }

            if ($transaction->isWithdrawal()) {
                SendTelegramActivityNotification::dispatch(
                    'transaction_withdrawal',
                    $transaction->id,
                    $performedBy
                );
            }
        });

        static::updated(function (Transaction $transaction) {
            if (! $transaction->wasChanged('status')) {
                Log::info('deposit_kyc_required_on_approval.event_skip', [
                    'transaction_id' => $transaction->id,
                    'company_id' => $transaction->company_id,
                    'user_id' => $transaction->user_id,
                    'reason' => 'status_not_changed',
                    'current_status' => strtolower((string) ($transaction->status ?? '')),
                ]);

                return;
            }

            $newStatus = strtolower((string) ($transaction->getChanges()['status'] ?? $transaction->status));

            if (! $transaction->isDeposit() || $newStatus !== 'approved') {
                Log::info('deposit_kyc_required_on_approval.event_skip', [
                    'transaction_id' => $transaction->id,
                    'company_id' => $transaction->company_id,
                    'user_id' => $transaction->user_id,
                    'reason' => ! $transaction->isDeposit() ? 'transaction_is_not_deposit' : 'new_status_is_not_approved',
                    'new_status' => $newStatus,
                    'is_deposit' => $transaction->isDeposit(),
                ]);

                return;
            }

            $previousCompany = app()->bound('company') ? app('company') : null;
            $company = $transaction->getCachedCompany();

            if ($company) {
                app()->instance('company', $company);
            }

            Log::info('deposit_kyc_required_on_approval.event', [
                'transaction_id' => $transaction->id,
                'company_id' => $transaction->company_id,
                'user_id' => $transaction->user_id,
                'new_status' => $newStatus,
                'send_kyc_required_on_approval' => $company?->setting('send_kyc_required_on_approval', 'no'),
                'user_verification_status' => $transaction->user?->verification_status,
                'company_bound' => app()->bound('company'),
            ]);

            try {
                app(EmailService::class)->sendDepositKycRequiredOnApprovalIfNeeded($transaction->fresh(['user']));
            } finally {
                if ($previousCompany) {
                    app()->instance('company', $previousCompany);
                } else {
                    app()->forgetInstance('company');
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDateAttribute($date): string
    {
        $date = $this->created_at;

        return Carbon::parse($date)->format('d M Y');
    }

    public function getTypeAttribute($value): string
    {
        if ($value == 'd') {
            return 'Deposit';
        }
        if ($value == 'w') {
            return 'Withdraw';
        }

        return $value;
    }

    public function getTimeAttribute($date)
    {
        return $this->created_at->format('H:i:s');
    }

    public function created_at_time()
    {

        return $this->created_at->format('H:i:s');

    }

    public function getReasonAttribute($val)
    {
        if (in_array($this->type, ['d', 'deposit'])) {
            $type_val = 'Dps ';
        } else {
            $type_val = $this->type;
        }

        if (! $val) {
            return $type_val.' _'.$this->account_id;
        } else {
            return $val;
        }
    }

    public function getTTypeAttribute()
    {
        return 'transaction';
    }

    public function getStatusAttribute($value)
    {
        if (! $value) {
            return 'pending';
        }

        return $value;
    }

    public function isDeposit(): bool
    {
        $type = strtolower((string) ($this->type ?? ''));

        return in_array($type, ['d', 'deposit', 'system_deposit'], true);
    }

    public function isWithdrawal(): bool
    {
        $type = strtolower((string) ($this->type ?? ''));

        return in_array($type, ['w', 'withdraw', 'withdrawal', 'system_withdrawal'], true);
    }

    public static function nextOrderId(): int
    {
        $current = self::withoutGlobalScopes()
            ->whereNotNull('order_id')
            ->max('order_id');

        return max(100010, (int) $current) + 1;
    }
}
