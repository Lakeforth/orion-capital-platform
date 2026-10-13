<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory, HasUuids, BelongsToCompany;

    protected $fillable = [
        'message',
        'sender_id',
        'receiver_id',
        'customer_id',
        'is_admin',
        'mail_sent_at',
        'read_at',
        'company_id',
        'conversation_type',
    ];

    protected $with = ['sender', 'attachments'];


    protected $casts = [
        'is_admin' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }


    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
