<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Data extends Model
{
    use BelongsToCompany, HasSlug;

    protected $casts = [
        'is_active' => 'boolean',
        'use_image' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $fillable = [
        'name', 'icon_key', 'slug', 'image', 'use_image', 'type', 'company_id', 'is_active',
    ];
}
