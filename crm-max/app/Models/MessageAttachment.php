<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'message_id',
        'file_name',
        'file_path',
        'file_size',
        'file_type',
    ];

    protected $appends = [
        'download_url',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function getDownloadUrlAttribute(): string
    {
        return $this->file_path;

    }
}
