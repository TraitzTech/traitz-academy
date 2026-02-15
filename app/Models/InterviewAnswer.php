<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewAnswer extends Model
{
    /** @use HasFactory<\Database\Factories\InterviewAnswerFactory> */
    use HasFactory;

    protected $fillable = [
        'interview_response_id',
        'interview_question_id',
        'answer',
        'is_correct',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points_earned' => 'integer',
        ];
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(InterviewResponse::class, 'interview_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(InterviewQuestion::class, 'interview_question_id');
    }
}
