<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitraceWalletAddress extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'company_id',
        'case_id',
        'wallet_address',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(BitraceCase::class, 'case_id');
    }
}
