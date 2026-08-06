<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohort extends Model
{
    /** @use HasFactory<\Database\Factories\CohortFactory> */
    use HasFactory;

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'start_date',
        'end_date',
        'intake_opens_at',
        'intake_closes_at',
        'status',
        'is_intake',
        'timezone',
        'expected_hours_per_day',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'intake_opens_at' => 'date',
            'intake_closes_at' => 'date',
            'is_intake' => 'boolean',
            'expected_hours_per_day' => 'decimal:1',
        ];
    }

    /**
     * Programs run in this cohort, each with its own supervisor (pivot).
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'cohort_program')
            ->withPivot('supervisor_id')
            ->withTimestamps();
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    /**
     * User IDs of interns in this cohort, for Assignment/LiveClass/Schedule
     * audience resolution (see {@see \App\Services\RosterResolver}).
     */
    public function studentIds(): \Illuminate\Support\Collection
    {
        return $this->internships()->pluck('user_id');
    }

    /**
     * The supervisor assigned to the given program within this cohort, if any.
     */
    public function supervisorIdForProgram(int $programId): ?int
    {
        $program = $this->relationLoaded('programs')
            ? $this->programs->firstWhere('id', $programId)
            : $this->programs()->where('programs.id', $programId)->first();

        $supervisorId = $program?->pivot?->supervisor_id;

        return $supervisorId !== null ? (int) $supervisorId : null;
    }

    public function hasProgram(int $programId): bool
    {
        return $this->relationLoaded('programs')
            ? $this->programs->contains('id', $programId)
            : $this->programs()->where('programs.id', $programId)->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOpenForIntake($query)
    {
        return $query->whereIn('status', [self::STATUS_UPCOMING, self::STATUS_ACTIVE]);
    }

    public function scopeIntake($query)
    {
        return $query->where('is_intake', true);
    }
}
