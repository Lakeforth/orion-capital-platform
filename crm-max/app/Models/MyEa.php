<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MyEa extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'expert_advisor_id',
        'amount',
        'company_id',
    ];

}
