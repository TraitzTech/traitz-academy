<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonVideoProgress extends Model
{
    /** Percentage of duration that counts as “watched” for completion (SRS). */
    public const COMPLETION_PERCENT_THRESHOLD = 90.0;

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
            'watched_seconds' => 'integer',
            'duration_seconds' => 'integer',
            'percentage' => 'decimal:2',
            'last_watched_at' => 'datetime',
        ];
    }

    public function isWatched(): bool
    {
        return (float) $this->percentage >= self::COMPLETION_PERCENT_THRESHOLD;
    }
}
