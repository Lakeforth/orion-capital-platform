<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSignal extends Model
{
    use BelongsToCompany;

    public const STATUSES = ['active', 'closed', 'expired'];

    protected $fillable = ['company_id', 'user_id', 'instrument', 'direction', 'entry_price', 'take_profit', 'stop_loss', 'status', 'result', 'note', 'sent_by', 'closed_at'];

    protected $casts = ['entry_price' => 'float', 'take_profit' => 'float', 'stop_loss' => 'float', 'closed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function code(): string
    {
        return 'SIG-'.(4500 + $this->id);
    }
}
