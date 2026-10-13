<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Exchange rate history for the LeadMax currency settings (written by App\Services\LeadMax\CurrencyRates). */
class CurrencyRateLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['code', 'old_rate', 'new_rate', 'change_percent', 'created_at'];

    protected $casts = ['old_rate' => 'float', 'new_rate' => 'float', 'change_percent' => 'float', 'created_at' => 'datetime'];
}
