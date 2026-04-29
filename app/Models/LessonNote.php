<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonNote extends Model
{
    /** @use HasFactory<\Database\Factories\LessonNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_lesson_id',
        'content',
        'timestamp',
        'timestamp_seconds',
    ];

    protected function casts(): array
    {
        return [
            'timestamp_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'course_lesson_id');
    }
}
