<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    /** Every event key this platform currently emits (or has UI toggles for). */
    public const EVENTS = [
        'drawdown_warning',
        'challenge_passed',
        'challenge_failed',
        'payout_processed',
        'scaling_update',
        'kyc_update',
        'system_announcement',
        'trade_closed',
    ];

    protected $fillable = [
        'user_id',
        'event_key',
        'in_app',
        'email',
        'telegram',
    ];

    protected $casts = [
        'in_app' => 'boolean',
        'email' => 'boolean',
        'telegram' => 'boolean',
    ];
}
