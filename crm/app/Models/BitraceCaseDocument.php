<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitraceCaseDocument extends Model
{
    use HasUuids;

    protected $fillable = [
        'case_id',
        'name',
        'file_path',
        'uploaded_by',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(BitraceCase::class, 'case_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
