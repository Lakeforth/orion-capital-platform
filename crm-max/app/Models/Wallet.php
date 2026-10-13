<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{

    use HasUuids, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'crypto',
        'crypto_network',
        'address',
        'balance',
        'status',
        'barcode',
        'is_general',
        'type',
        'is_active',
    ];

    public function getBarcodeAttribute($value)
    {
        if(!$value && $this->type == 'wallet') {
            return asset('images/not_found.png');
        }
        return $value;
    }
}
