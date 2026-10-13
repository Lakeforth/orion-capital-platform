<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EaConfiguration extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'company_id',
        'expert_advisor_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function expertAdvisor(): BelongsTo
    {
        return $this->belongsTo(ExpertAdvisor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
