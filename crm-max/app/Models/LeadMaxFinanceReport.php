<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A finance report generated from LeadMax > Finances > Reports (the CSV lives on the local disk). */
class LeadMaxFinanceReport extends Model
{
    use BelongsToCompany;

    protected $table = 'leadmax_finance_reports';

    protected $fillable = ['company_id', 'name', 'type', 'category', 'from', 'to', 'status', 'path', 'rows', 'created_by'];

    protected $casts = ['from' => 'date', 'to' => 'date', 'rows' => 'integer'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function code(): string
    {
        return 'RPT-'.$this->created_at?->format('Ymd').'-'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }
}
