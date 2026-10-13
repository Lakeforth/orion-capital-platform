<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class ExpertAdvisor extends Model
{
    use HasUuids, HasSlug;

    protected $fillable = [
        'name','image','info','amount', 'active','slug',
        'type', 'popular', 'sales', 'rating','description','company_id',
    ];



    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }


    protected $casts = [
        'active' => 'boolean',
        'popular' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getImageAttribute($value): string
    {
        if(!$value) {
            return asset('default/avatar.png');
        }
        return $value;
    }

}
