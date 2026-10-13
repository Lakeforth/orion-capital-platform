<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadInvoice extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = ['company_id', 'user_id', 'type', 'number', 'invoice_id', 'items', 'amount', 'currency', 'issue_date', 'due_date', 'status', 'notes', 'created_by', 'sent_at', 'paid_at'];

    protected $casts = ['items' => 'array', 'amount' => 'decimal:2', 'issue_date' => 'date', 'due_date' => 'date', 'sent_at' => 'datetime', 'paid_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(self::class, 'invoice_id')->where('type', 'credit_note')->where('status', '!=', 'void');
    }

    public function isCreditNote(): bool
    {
        return $this->type === 'credit_note';
    }

    /** Invoice total less its issued credit notes. */
    public function balanceDue(): float
    {
        return round((float) $this->amount - (float) $this->creditNotes->sum('amount'), 2);
    }
}
