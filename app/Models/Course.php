<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'overview',
        'category',
        'level',
        'price',
        'is_free',
        'duration',
        'total_lessons',
        'thumbnail_url',
        'intro_video_url',
        'outcomes',
        'requirements',
        'curriculum',
        'status',
        'is_featured',
        'enrolled_count',
        'rating',
        'review_count',
        'tutor_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_free'      => 'boolean',
            'is_featured'  => 'boolean',
            'outcomes'     => 'array',
            'requirements' => 'array',
            'curriculum'   => 'array',
            'price'        => 'decimal:2',
            'rating'       => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
