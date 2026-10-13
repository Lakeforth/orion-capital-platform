<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['user_id','note','by_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function by(): BelongsTo
    {
        return $this->belongsTo(User::class,'by_id');
    }
}
