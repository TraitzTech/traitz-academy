<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseLesson extends Model
{
    /** @use HasFactory<\Database\Factories\CourseLessonFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'course_section_id',
        'title',
        'description',
        'type',
        'video_url',
        'youtube_video_id',
        'youtube_status',
        'youtube_error',
        'content',
        'duration',
        'is_free',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'lesson_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class, 'course_lesson_id')->orderBy('sort_order');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class, 'lesson_id');
    }
}
