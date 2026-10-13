<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'source',
        'origin',
        'language',
        'welcome_message_enabled',
        'welcome_message_id',
        'is_active',
        'use_new_survey',
        'webhook_url',
        'tags',
        'notes',
        'metadata',
        'last_lead_fetched',
        'send_to_third_party',
        'product_id',
        'end_zone',
        'whatsapp_number',
        'whatsapp_message',
        'owner',
        'cold_lead_chat_id',
        'warm_lead_chat_id',
        'hot_lead_chat_id',
        'campaign_logo',
        'campaign_favicon',
        'tracking_script',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'welcome_message_enabled' => 'integer',
        'is_active' => 'boolean',
        'use_new_survey' => 'integer',
        'last_lead_fetched' => 'datetime',
        'send_to_third_party' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function welcomeMessage(): BelongsTo
    {
        return $this->belongsTo(WelcomeMessage::class);
    }

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
