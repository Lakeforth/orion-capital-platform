<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BitraceCasePhase extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_id',
        'name',
        'order',
        'status',
        'is_active',
        'activated_at',
        'completed_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'order'        => 'integer',
        'activated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(BitraceCase::class, 'case_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(BitracePhaseAction::class, 'phase_id')->with('actionType');
    }
}
