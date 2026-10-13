<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitraceCaseActivity extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_id',
        'type',
        'description',
        'actor',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(BitraceCase::class, 'case_id');
    }
}
