<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    public function canUserJoin(User $user): bool
    {
        if ($user->canAccessAdminPanel()) {
            return true;
        }

        if ((int) $this->tutor_id === (int) $user->id) {
            return true;
        }

        if ($this->access_type === 'course') {
            $courseIds = $this->courses()->pluck('courses.id');
            if ($courseIds->isEmpty()) {
                return false;
            }

            return Enrollment::query()
                ->where('user_id', $user->id)
                ->whereIn('course_id', $courseIds)
                ->whereIn('access_status', ['active', 'completed'])
                ->exists();
        }

        return $this->students()->where('users.id', $user->id)->exists();
    }
}
