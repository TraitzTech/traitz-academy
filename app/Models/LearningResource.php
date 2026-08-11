<?php

namespace App\Models;

use App\Concerns\HasAttachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningResource extends Model
{
    use HasAttachable;

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
        'attachable_type',
        'attachable_id',
        'created_by',
        'audience',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function selectedStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_resource_student', 'learning_resource_id', 'student_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id');
    }

    /** Admin-authored resources published to the public /resources library. */
    public function scopeGlobal($query)
    {
        return $query->whereNull('attachable_id');
    }
}
