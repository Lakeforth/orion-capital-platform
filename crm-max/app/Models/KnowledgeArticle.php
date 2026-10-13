<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class KnowledgeArticle extends Model
{
    use HasUuids;

    protected $fillable = [
        'company_id',
        'title',
        'slug',
        'body',
        'category',
        'difficulty',
        'is_published',
        'featured',
        'published_at',
        'order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'featured' => 'boolean',
        'published_at' => 'datetime',
    ];
}
