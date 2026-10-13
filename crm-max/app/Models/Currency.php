<?php

namespace App\Models;

use App\Services\LeadMax\CurrencyRates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'symbol', 'code', 'rate',
        'icon',
        'is_asset',
        'asset_key',
        'is_default'];

    protected static function booted(): void
    {
        // Keep a throttled rate history for the LeadMax currency settings and rate alerts.
        static::updated(function (Currency $currency) {
            if ($currency->wasChanged('rate')) {
                rescue(fn () => app(CurrencyRates::class)->record($currency), report: false);
            }
        });
    }
}
