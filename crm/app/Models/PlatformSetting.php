<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = ['settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Singleton row -- there is only ever one platform_settings record.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['settings' => []]);
    }
}
