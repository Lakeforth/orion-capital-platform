<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;
    use HasUuids;



    protected $fillable = [
        'name',
        'dial_code',
        'code',
        'flag',
        'currency_name',
        'currency_code',
        'currency_symbol',
        'dial_min_length',
        'dial_max_length',
        'is_active',
    ];

    public static function seedData()
    {
        $path = dirname(__FILE__, 2) . '/seedData/countries/countries.json';

        if (file_exists($path)) {
            $contents = file_get_contents($path);
            return json_decode($contents, true);
        }

        return [];
    }
}
