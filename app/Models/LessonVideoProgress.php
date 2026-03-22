<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonVideoProgress extends Model
{
    /** @use HasFactory<\Database\Factories\LessonVideoProgressFactory> */
    use HasFactory;

    protected $table = 'lesson_video_progress';

    protected $fillable = [
        'user_id',
        'course_lesson_id',
        'watched_seconds',
        'duration_seconds',
        'percentage',
        'last_watched_at',
    ];

    protected function casts(): array
    {
        return [
            'watched_seconds'  => 'integer',
            'duration_seconds' => 'integer',
            'percentage'       => 'decimal:2',
            'last_watched_at'  => 'datetime',
        ];
    }

    // considered watched if 80% or more has been viewed
    public function isWatched(): bool
    {
        return $this->percentage >= 80.00;
    }
}
