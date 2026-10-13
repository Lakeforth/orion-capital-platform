<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitracePhaseAction extends Model
{
    use HasUuids;

    protected $fillable = [
        'phase_id',
        'action_type_id',
        'status',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(BitraceCasePhase::class, 'phase_id');
    }

    public function actionType(): BelongsTo
    {
        return $this->belongsTo(BitraceActionType::class, 'action_type_id');
    }
}
