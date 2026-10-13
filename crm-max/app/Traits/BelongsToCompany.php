<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        // Auto-assign company ID on model creation
        static::creating(function (Model $model) {
            if (!$model->company_id && app()->has('company')) {
                $model->company_id = app('company')->id;
            }
        });

        // Global scope to filter by company
        static::addGlobalScope('company', function (Builder $builder) {
            if (app()->has('company')) {
                $builder->where('company_id', app('company')->id);
            }
        });

        // Clear related caches when model is updated
        static::saved(function (Model $model) {
            // Generate a cache key based on the model's class and company ID
            $cacheKey = self::getCacheKey($model);
            Cache::forget($cacheKey);

            // Also clear any list cache keys
            Cache::forget("{$cacheKey}:list");
        });

        static::deleted(function (Model $model) {
            // Clear caches when a model is deleted
            $cacheKey = self::getCacheKey($model);
            Cache::forget($cacheKey);
            Cache::forget("{$cacheKey}:list");
        });
    }

    /**
     * Generate a cache key for this model
     */
    protected static function getCacheKey(Model $model): string
    {
        $class = strtolower(class_basename($model));
        return "company:{$model->company_id}:{$class}:{$model->id}";
    }

    /**
     * Define relationship to Company
     */
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    /**
     * Get cached company for this model
     */
    public function getCachedCompany()
    {
        return \App\Models\Company::findCached($this->company_id);
    }
}
