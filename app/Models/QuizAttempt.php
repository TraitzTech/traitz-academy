<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    /** @use HasFactory<\Database\Factories\QuizAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_lesson_id',
        'answers',
        'score',
        'total_points',
        'percentage',
        'passed',
        'started_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers'      => 'array',
            'score'        => 'integer',
            'total_points' => 'integer',
            'percentage'   => 'decimal:2',
            'passed'       => 'boolean',
            'started_at'   => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }
}
