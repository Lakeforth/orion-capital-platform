<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'ip_address',
        'device_type',
        'browser',
        'platform',
        'location',
        'status',
    ];
}
