<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** How and from whom a lead arrived, captured once at intake (see LeadCapture). */
class LeadAttribution extends Model
{
    use BelongsToCompany;

    public const LEGACY = 'legacy_backfill';

    protected $fillable = [
        'company_id', 'lead_id', 'received_at', 'intake_channel', 'source_name', 'campaign_id', 'campaign_name',
        'referrer_kind', 'referrer_id', 'referrer_name', 'referrer_host', 'landing_path',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'external_lead_id', 'import_id', 'import_row', 'created_by', 'assigned_owner_id', 'assignment_method', 'source_origin',
    ];

    protected $casts = ['received_at' => 'datetime'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id');
    }
}
