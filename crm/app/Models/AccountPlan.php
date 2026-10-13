<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AccountPlan extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = [
        'title',
        'color',
        'icon',
        'leverage',
        'features',
        'description',
        'min_deposit',
        'price',
        'image',
        'company_id',
        'expert_advisors',
    ];

    protected $casts = [
        'features' => 'array',
        'expert_advisors' => 'array',
    ];

    protected function price(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! app()->bound('company') || ! app('company')) {
                    return (float) $value;
                }

                return module_enabled('enable_plan_price') ? (float) $value : 0.0;
            },
            set: function ($value) {
                if (! app()->bound('company') || ! app('company')) {
                    return (float) ($value ?? 0);
                }

                return module_enabled('enable_plan_price') ? (float) ($value ?? 0) : 0.0;
            },
        );
    }

    public function getFeature(int $index, $default = null)
    {
        return $this->features[$index] ?? $default;
    }

    public function getEAs()
    {
        $eas = $this->expert_advisors ?? [];
        if (empty($eas)) {
            return [];
        }

        return ExpertAdvisor::query()->whereIn('id', $eas)->get();
    }

    public function hasFeature(int $index): bool
    {
        return isset($this->features[$index]) && ! empty($this->features[$index]);
    }
}
