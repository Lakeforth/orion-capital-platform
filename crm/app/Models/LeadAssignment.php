<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadAssignment extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'lead_id', 'agent_id', 'rule_id', 'method', 'via', 'actor_id'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(LeadSplitRule::class, 'rule_id');
    }
}
