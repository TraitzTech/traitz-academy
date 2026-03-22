<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackResponse extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'feedback_form_id',
        'user_id',
        'is_anonymous',
        'respondent_name',
        'respondent_email',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(FeedbackForm::class, 'feedback_form_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FeedbackAnswer::class);
    }

    public function displayName(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous';
        }

        return $this->respondent_name
            ?? $this->user?->name
            ?? 'Unknown';
    }
}
