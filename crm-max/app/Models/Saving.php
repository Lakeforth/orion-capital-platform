<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Saving extends Model
{
    use HasUuids, BelongsToCompany;

    protected $fillable = [
        'title',
        'currency',
        'periods',
        'status',
        'period',
        'company_id',
    ];

    protected $casts = [
        'id'       => 'string',
        'periods'  => 'array',
        'status'   => 'boolean',
    ];

    public function getDaysFromPeriod($period): int
    {
        if($period == '1_month'){
            return 30;
        }
        if($period == '3_months'){
            return 90;
        }
        if($period == '6_months'){
            return 180;
        }
        if($period == '1_year'){
            return 365;
        }
        return 0;
    }

    public function getEndDateFromPeriod(string $period): ?string
    {

        $start = Carbon::now();

        switch ($period) {
            case '1_month':
                $end = $start->copy()->addMonth();
                break;
            case '3_months':
                $end = $start->copy()->addMonths(3);
                break;
            case '6_months':
                $end = $start->copy()->addMonths(6);
                break;
            case '12_months':
            case '1_year':
                $end = $start->copy()->addYear();
                break;
            default:
                return null;
        }

        return $end->toDateString();
    }

    public function getUSDAmount($currency, $amount): float|int
    {
        $currency = Currency::query()->where('code', $currency)->first();
        if ($currency) {
            return $amount / $currency->rate;
        }
        return $amount;
    }
}
