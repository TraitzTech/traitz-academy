<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewQuestion extends Model
{
    /** @use HasFactory<\Database\Factories\InterviewQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'interview_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'points',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'points' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(InterviewAnswer::class);
    }
}
