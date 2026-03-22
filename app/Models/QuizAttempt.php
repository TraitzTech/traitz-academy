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
        'quiz_id',
        'answers',
        'score_percentage',
        'passed',
        'instructor_feedback',
        'status',
        'started_at',
        'submitted_at',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'answers'          => 'array',
            'score_percentage' => 'decimal:2',
            'passed'           => 'boolean',
            'started_at'       => 'datetime',
            'submitted_at'     => 'datetime',
            'graded_at'        => 'datetime',
        ];
    }

    public function isGraded(): bool
    {
        return $this->status === 'graded';
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
