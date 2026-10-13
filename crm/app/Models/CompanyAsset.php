<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAsset extends Model
{
    protected $fillable = [
        'company_id',
        'assets',
    ];

    protected $casts = [
        'assets' => 'array',
        'asset_categories' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
