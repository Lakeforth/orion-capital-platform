<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'user_id','expires_at','token','company_id'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
