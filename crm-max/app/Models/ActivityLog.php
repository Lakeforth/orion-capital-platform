<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['title','info','user_id','company_id','type'];

    protected $casts = [
        'info' => 'array',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
