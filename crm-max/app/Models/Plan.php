<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasUuids;

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    protected $fillable = [
        'title',
        'price',
        'interval', // e.g., 'monthly', 'yearly'
        'period', // e.g., number of intervals
        'color',
        'features',
        'description',
        'image',
        'expert_advisors',
        'type', // e.g. 'brokerage', 'propfirm'
        'limits', // keyed map of plan-limit values; see config/plan_limits.php
    ];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
    ];

    public function getFeature(int $index, $default = null)
    {
        $feature = $this->features[$index] ?? null;

        if (is_array($feature)) {
            return $feature['value'] ?? $default;
        }

        return $feature ?? $default;
    }

    public static function lowestAmountPlan(): ?self
    {
        return self::query()
            ->orderBy('price')
            ->orderBy('title')
            ->first();
    }

    public function hasFeature(int $index): bool
    {
        if (! isset($this->features[$index])) {
            return false;
        }

        $feature = $this->features[$index];
        if (is_array($feature)) {
            $value = $feature['value'] ?? null;
            $type = $feature['type'] ?? null;

            if ($type === 'boolean') {
                return (bool) $value;
            }

            return $value !== null && $value !== '';
        }

        return ! empty($feature);
    }
}
