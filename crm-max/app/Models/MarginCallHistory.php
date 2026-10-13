<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarginCallHistory extends Model
{
    use HasUuids;

    protected $fillable = ['user_id','margin_level','equity','used_margin'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
