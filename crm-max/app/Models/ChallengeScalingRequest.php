<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeScalingRequest extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = [
        'company_id',
        'user_id',
        'challenge_id',
        'requested_account_size',
        'status',
        'admin_note',
        'processed_at',
    ];

    protected $casts = [
        'requested_account_size' => 'float',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }
}
