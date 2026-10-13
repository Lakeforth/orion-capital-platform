<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class LeadSplitRule extends Model
{
    use BelongsToCompany;

    public const METHODS = ['round_robin' => 'Round Robin', 'weighted' => 'Weighted', 'specific' => 'Specific Agent', 'balanced' => 'Balanced'];

    protected $fillable = ['company_id', 'name', 'source', 'region', 'countries', 'method', 'agents', 'status', 'priority', 'cursor', 'created_by'];

    protected $casts = ['countries' => 'array', 'agents' => 'array', 'priority' => 'integer', 'cursor' => 'integer'];

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }

    /** @return array<string, int> agent id => weight */
    public function agentWeights(): array
    {
        return collect($this->agents ?? [])->mapWithKeys(fn ($a) => [(string) $a['id'] => max(1, (int) ($a['weight'] ?? 1))])->all();
    }

    public function regionLabel(): string
    {
        return $this->region ?: (empty($this->countries) ? 'All Countries' : implode(', ', array_slice($this->countries, 0, 3)).(count($this->countries) > 3 ? ' +'.(count($this->countries) - 3) : ''));
    }
}
