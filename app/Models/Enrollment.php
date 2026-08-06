<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Enrollment extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    /**
     * Access statuses that grant a learner access to paid course content.
     * Canonical definition — do not inline this list in queries; use
     * {@see scopeGrantsAccess()} or {@see Course::grantsAccessTo()} instead.
     */
    public const ACCESS_GRANTING_STATUSES = ['active', 'completed'];

    protected $fillable = [
        'user_id',
        'course_id',
        'instalment_plan_id',
        'payment_type',
        'access_status',
        'progress',
        'enrolled_at',
        'completed_at',
        'consecutive_failed_payments',
        'instalment_next_due_at',
        'last_instalment_reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
            'consecutive_failed_payments' => 'integer',
            'instalment_next_due_at' => 'datetime',
            'last_instalment_reminder_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isActive(): bool
    {
        return $this->access_status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->access_status === 'suspended';
    }

    public function isCompleted(): bool
    {
        return $this->access_status === 'completed';
    }

    public function scopeActive($query)
    {
        return $query->where('access_status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('access_status', 'completed');
    }

    public function scopeSuspended($query)
    {
        return $query->where('access_status', 'suspended');
    }

    /**
     * Enrollments that currently grant the learner access to course content.
     * This is the single source of truth for "is this learner allowed in?".
     */
    public function scopeGrantsAccess($query)
    {
        return $query->whereIn('access_status', self::ACCESS_GRANTING_STATUSES);
    }

    /**
     * Unique learners (non-revoked) across all courses owned by this instructor.
     */
    public static function countDistinctUsersForInstructor(int $instructorId): int
    {
        $row = DB::table('enrollments')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->where('courses.instructor_id', $instructorId)
            ->where('enrollments.access_status', '!=', 'revoked')
            ->selectRaw('count(distinct enrollments.user_id) as aggregate')
            ->first();

        return (int) ($row->aggregate ?? 0);
    }

    /**
     * Unique users with at least one non-revoked LMS enrollment (platform-wide).
     */
    public static function countDistinctNonRevokedUsers(): int
    {
        $row = DB::table('enrollments')
            ->where('access_status', '!=', 'revoked')
            ->selectRaw('count(distinct user_id) as aggregate')
            ->first();

        return (int) ($row->aggregate ?? 0);
    }
}
