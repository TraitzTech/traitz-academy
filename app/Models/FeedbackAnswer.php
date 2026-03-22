<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'feedback_response_id',
        'feedback_question_id',
        'answer',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(FeedbackResponse::class, 'feedback_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(FeedbackQuestion::class, 'feedback_question_id');
    }
}
