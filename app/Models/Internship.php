<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Internship extends Model
{
    /** @use HasFactory<\Database\Factories\InternshipFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_TERMINATED = 'terminated';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const MODE_ONSITE = 'onsite';

    public const MODE_REMOTE = 'remote';

    public const MODE_HYBRID = 'hybrid';

    protected $fillable = [
        'cohort_id',
        'program_id',
        'user_id',
        'application_id',
        'supervisor_id',
        'start_date',
        'logbook_starts_on',
        'end_date',
        'status',
        'work_mode',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'logbook_starts_on' => 'date',
            'end_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The date from which logbook entries are expected (and missed days start
     * counting). Falls back to start_date when not set explicitly.
     */
    public function effectiveLogbookStart(): ?\Carbon\CarbonInterface
    {
        return $this->logbook_starts_on ?? $this->start_date;
    }

    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function intern(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(InternshipAttendance::class);
    }

    public function logbookEntries(): HasMany
    {
        return $this->hasMany(LogbookEntry::class);
    }

    /**
     * The user who actually supervises this intern: the per-intern override if
     * set, otherwise the supervisor assigned to this intern's program within
     * the cohort.
     */
    public function effectiveSupervisorId(): ?int
    {
        if ($this->supervisor_id !== null) {
            return (int) $this->supervisor_id;
        }

        return $this->cohort?->supervisorIdForProgram((int) $this->program_id);
    }

    public function isSupervisedBy(User $user): bool
    {
        $supervisorId = $this->effectiveSupervisorId();

        return $supervisorId !== null && (int) $supervisorId === (int) $user->id;
    }

    /**
     * The timezone attendance/logbook dates are reckoned in (cohort's, else app).
     */
    public function timezone(): string
    {
        return $this->cohort?->timezone ?? config('app.timezone', 'UTC');
    }

    /**
     * Whether the given date is a day the logbook is compulsory for
     * (independent of office attendance — see config('internship.logbook')).
     */
    public function isWorkingDay(\Carbon\CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, config('internship.logbook.working_days', [1, 2, 3, 4, 5]), true);
    }

    /**
     * Whether a logbook entry has been submitted (at any point, regardless of
     * its current review status) for the given date.
     */
    public function hasLogbookEntryFor(\Carbon\CarbonInterface $date): bool
    {
        return $this->logbookEntries()
            ->whereDate('date', $date)
            ->whereNotNull('submitted_at')
            ->exists();
    }

    /**
     * Working days elapsed from start_date through today (or end_date, if the
     * internship has already ended), in the internship's timezone.
     */
    public function workingDaysElapsed(): int
    {
        $effectiveStart = $this->effectiveLogbookStart();
        if (! $effectiveStart) {
            return 0;
        }

        $timezone = $this->timezone();
        $start = $effectiveStart->copy()->timezone($timezone)->startOfDay();
        $today = now($timezone)->startOfDay();
        $end = $this->end_date
            ? $this->end_date->copy()->timezone($timezone)->startOfDay()->min($today)
            : $today;

        if ($end->lessThan($start)) {
            return 0;
        }

        // Date::use(CarbonImmutable::class) is set app-wide, so addDay()
        // returns a new instance rather than mutating — reassign each pass.
        $count = 0;
        $cursor = $start;
        while ($cursor->lessThanOrEqualTo($end)) {
            if ($this->isWorkingDay($cursor)) {
                $count++;
            }
            $cursor = $cursor->addDay();
        }

        return $count;
    }

    /**
     * Working days elapsed with no submitted logbook entry, floored at 0.
     */
    public function missedLogbookDaysCount(): int
    {
        $submittedDays = $this->logbookEntries()
            ->whereNotNull('submitted_at')
            ->distinct()
            ->count('date');

        return max(0, $this->workingDaysElapsed() - $submittedDays);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForSupervisor($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('supervisor_id', $user->id)
                ->orWhere(function ($sub) use ($user) {
                    $sub->whereNull('supervisor_id')
                        ->whereExists(function ($ex) use ($user) {
                            $ex->selectRaw('1')
                                ->from('cohort_program')
                                ->whereColumn('cohort_program.cohort_id', 'internships.cohort_id')
                                ->whereColumn('cohort_program.program_id', 'internships.program_id')
                                ->where('cohort_program.supervisor_id', $user->id);
                        });
                });
        });
    }
}
