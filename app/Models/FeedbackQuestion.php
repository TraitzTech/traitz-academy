<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackQuestion extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'feedback_form_id',
        'question',
        'type',
        'options',
        'required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(FeedbackForm::class, 'feedback_form_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FeedbackAnswer::class);
    }
}
