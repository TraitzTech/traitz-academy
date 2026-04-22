<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    /** @use HasFactory<\Database\Factories\QuizFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'instructions',
        'pass_mark_percentage',
        'max_attempts',
        'is_required',
        'reveal_answers',
    ];

    protected function casts(): array
    {
        return [
            'pass_mark_percentage' => 'decimal:2',
            'max_attempts' => 'integer',
            'is_required' => 'boolean',
            'reveal_answers' => 'boolean',
        ];
    }

    public function isCourseLevel(): bool
    {
        return is_null($this->lesson_id);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
