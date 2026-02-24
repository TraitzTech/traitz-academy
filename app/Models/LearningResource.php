<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningResource extends Model
{
    /** @use HasFactory<\Database\Factories\LearningResourceFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'description',
        'document_path',
        'youtube_url',
        'external_url',
        'content',
        'tags',
        'sort_order',
        'is_active',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id');
    }
}
