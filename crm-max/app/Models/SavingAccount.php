<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingAccount extends Model
{
    use HasUuids, BelongsToCompany;

    protected $fillable = [
        'user_id',
        'saving_id',
        'currency',
        'period',
        'days',
        'is_flexible',
        'amount',
        'earned',
        'start_date',
        'end_date',
        'usd_amount',
        'days_elapsed',
        'company_id',
        'roi','completed','completed_at'
    ];

//    public function saving()
//    {
//        return $this->belongsTo(Saving::class);
//    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
