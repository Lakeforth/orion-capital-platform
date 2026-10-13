<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitraceTransferRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'case_id',
        'phase_action_id',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(BitraceCase::class, 'case_id');
    }

    public function phaseAction(): BelongsTo
    {
        return $this->belongsTo(BitracePhaseAction::class, 'phase_action_id');
    }
}
