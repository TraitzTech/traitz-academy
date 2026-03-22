<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewResponse extends Model
{
    /** @use HasFactory<\Database\Factories\InterviewResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'interview_id',
        'user_id',
        'application_id',
        'score',
        'total_points',
        'percentage',
        'status',
        'passed',
        'requires_manual_review',
        'reviewed_at',
        'reviewed_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'total_points' => 'integer',
            'percentage' => 'decimal:2',
            'passed' => 'boolean',
            'requires_manual_review' => 'boolean',
            'reviewed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(InterviewAnswer::class);
    }
}
