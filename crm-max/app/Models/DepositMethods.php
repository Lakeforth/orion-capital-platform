<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositMethods extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'type',
        'link',
        'name',
        'image',
        'company_id',
        'details',
    ];
}
