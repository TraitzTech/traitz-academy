<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'program_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'bio',
        'education_level',
        'institution_name',
        'academic_duration',
        'motivation',
        'experience',
        'internship_letter_path',
        'status',
        'application_type',
        'notes',
        'reviewed_at',
        'interview_id',
        'interview_scheduled_at',
        'interview_status',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'interview_scheduled_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function interviewResponses(): HasMany
    {
        return $this->hasMany(InterviewResponse::class);
    }
}
