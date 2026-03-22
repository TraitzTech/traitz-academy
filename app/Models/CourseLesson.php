<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'content',
        'duration',
        'is_free',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_free'    => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }
}
