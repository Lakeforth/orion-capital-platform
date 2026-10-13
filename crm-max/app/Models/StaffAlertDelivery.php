<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A staff email alert about a lead and what happened to it: queued, sent, failed (after the job's
 * retries) or skipped (the lead's alerts were switched off, or the recipient changed, before it went).
 * company_id is set explicitly -- alerts are written from jobs and commands with no tenant bound.
 */
class StaffAlertDelivery extends Model
{
    protected $fillable = [
        'company_id', 'lead_id', 'recipient_id', 'recipient_email', 'event', 'dedupe_key',
        'context', 'status', 'attempts', 'last_error', 'sent_at',
    ];

    protected $casts = [
        'context' => 'array',
        'sent_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id')->withoutGlobalScopes();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id')->withoutGlobalScopes();
    }
}
