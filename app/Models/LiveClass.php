<?php

namespace App\Models;

use App\Services\RosterResolver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LiveClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'tutor_id',
        'created_by',
        'start_time',
        'duration',
        'room_name',
        'meeting_url',
        'meeting_provider',
        'meeting_event_id',
        'access_type',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'duration' => 'integer',
        ];
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'live_class_courses');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'live_class_students', 'live_class_id', 'student_id');
    }

    /**
     * Polymorphic replacement for courses() — a live class can be linked to
     * any mix of Course/Cohort/Program rows via live_class_targets.
     *
     * @return Collection<int, object{target_type: string, target_id: int}>
     */
    public function targetRows(): Collection
    {
        return DB::table('live_class_targets')
            ->where('live_class_id', $this->id)
            ->get(['target_type', 'target_id']);
    }

    /**
     * User IDs of everyone reachable through this class's targets (Course
     * enrollments, Cohort/Program interns). Falls back to the legacy
     * courses() pivot if no live_class_targets rows exist yet.
     */
    public function resolveAudienceIds(): Collection
    {
        $rows = $this->targetRows();

        if ($rows->isEmpty()) {
            $courseIds = $this->courses()->pluck('courses.id');

            return $courseIds->isEmpty()
                ? collect()
                : Enrollment::query()->whereIn('course_id', $courseIds)->grantsAccess()->pluck('user_id')->unique()->values();
        }

        $resolver = app(RosterResolver::class);

        return $rows
            ->flatMap(function ($row) use ($resolver) {
                $model = class_exists($row->target_type) ? $row->target_type::find($row->target_id) : null;

                return $model ? $resolver->resolveStudentIds($model) : collect();
            })
            ->unique()
            ->values();
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(LiveClassAttendance::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(LiveClassMessage::class)->latest();
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(LiveClassRecording::class)->latest();
    }

    public function endsAt(): CarbonInterface
    {
        return $this->start_time->copy()->addMinutes($this->duration);
    }

    public function studentJoinOpensAt(): CarbonInterface
    {
        return $this->start_time->copy()->subMinutes(5);
    }

    public function canStudentJoinNow(?CarbonInterface $now = null): bool
    {
        $time = $now ?? now();

        return $time->greaterThanOrEqualTo($this->studentJoinOpensAt())
            && $time->lessThanOrEqualTo($this->endsAt());
    }

    public function hostPresenceCacheKey(): string
    {
        return "live-class:{$this->id}:host-online";
    }

    public function markHostOnline(int $ttlSeconds = 90): void
    {
        Cache::put($this->hostPresenceCacheKey(), true, now()->addSeconds($ttlSeconds));
    }

    public function clearHostOnline(): void
    {
        Cache::forget($this->hostPresenceCacheKey());
    }

    public function hasHostOnline(): bool
    {
        return (bool) Cache::get($this->hostPresenceCacheKey(), false);
    }

    /**
     * Whether the user can host/moderate this class (view any time, mark host
     * presence, moderate). Admin-panel staff, or the class's own tutor.
     */
    public function isManageableBy(User $user): bool
    {
        return $user->canAccessAdminPanel() || (int) $this->tutor_id === (int) $user->id;
    }

    /**
     * Classes a user may see, filtered at the query level (avoids loading every
     * class and calling canUserJoin() per row). Mirrors canUserJoin().
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canAccessAdminPanel()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('tutor_id', $user->id)
                ->orWhere(function ($sub) use ($user) {
                    $sub->where('access_type', 'course')
                        ->where(function ($group) use ($user) {
                            $group->whereHas('courses.enrollments', function ($e) use ($user) {
                                $e->where('user_id', $user->id)->grantsAccess();
                            })
                                ->orWhereExists(function ($ex) use ($user) {
                                    $ex->selectRaw('1')
                                        ->from('live_class_targets')
                                        ->whereColumn('live_class_targets.live_class_id', 'live_classes.id')
                                        ->where('live_class_targets.target_type', Cohort::class)
                                        ->whereExists(function ($intern) use ($user) {
                                            $intern->selectRaw('1')
                                                ->from('internships')
                                                ->whereColumn('internships.cohort_id', 'live_class_targets.target_id')
                                                ->where('internships.user_id', $user->id);
                                        });
                                })
                                ->orWhereExists(function ($ex) use ($user) {
                                    $ex->selectRaw('1')
                                        ->from('live_class_targets')
                                        ->whereColumn('live_class_targets.live_class_id', 'live_classes.id')
                                        ->where('live_class_targets.target_type', Program::class)
                                        ->whereExists(function ($intern) use ($user) {
                                            $intern->selectRaw('1')
                                                ->from('internships')
                                                ->whereColumn('internships.program_id', 'live_class_targets.target_id')
                                                ->where('internships.user_id', $user->id);
                                        });
                                });
                        });
                })
                ->orWhere(function ($sub) use ($user) {
                    $sub->where('access_type', 'custom')
                        ->whereHas('students', fn ($s) => $s->where('users.id', $user->id));
                });
        });
    }

    public function canUserJoin(User $user): bool
    {
        if ($this->isManageableBy($user)) {
            return true;
        }

        if ($this->access_type === 'course') {
            return $this->resolveAudienceIds()->contains((int) $user->id);
        }

        return $this->students()->where('users.id', $user->id)->exists();
    }
}
