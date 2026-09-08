<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const ROLE_USER = 'user';

    public const ROLE_TUTOR = 'tutor';

    public const ROLE_CTO = 'cto';

    public const ROLE_CEO = 'ceo';

    public const ROLE_PROGRAM_COORDINATOR = 'program_coordinator';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_ADMIN_LEGACY = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'phone_required_prompted',
        'withdrawal_pin',
        'notification_preferences',
        'google_calendar_refresh_token',
        'google_calendar_email',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'withdrawal_pin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'withdrawal_pin' => 'hashed',
            'notification_preferences' => 'array',
        ];
    }

    public function personalScheduleEvents(): HasMany
    {
        return $this->hasMany(StudentScheduleEvent::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function sentEmailCampaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'admin_id');
    }

    public function emailCampaignRecipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function interviewResponses(): HasMany
    {
        return $this->hasMany(InterviewResponse::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function coursePayments(): HasMany
    {
        return $this->hasMany(CoursePayment::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function liveClassesAsTutor(): HasMany
    {
        return $this->hasMany(LiveClass::class, 'tutor_id');
    }

    public function liveClassesCreated(): HasMany
    {
        return $this->hasMany(LiveClass::class, 'created_by');
    }

    public function liveClassMessages(): HasMany
    {
        return $this->hasMany(LiveClassMessage::class);
    }

    public function liveClassAttendance(): HasMany
    {
        return $this->hasMany(LiveClassAttendance::class, 'student_id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function lessonNotes(): HasMany
    {
        return $this->hasMany(LessonNote::class);
    }

    public function assignmentsCreated(): HasMany
    {
        return $this->hasMany(Assignment::class, 'created_by');
    }

    public function selectedAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Assignment::class, 'assignment_student', 'student_id', 'assignment_id')
            ->withTimestamps();
    }

    public function schedulesCreated(): HasMany
    {
        return $this->hasMany(LmsSchedule::class, 'created_by');
    }

    public function selectedSchedules(): BelongsToMany
    {
        return $this->belongsToMany(LmsSchedule::class, 'lms_schedule_student', 'student_id', 'lms_schedule_id')
            ->withTimestamps();
    }

    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function recordedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'recorded_by');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function hasWithdrawalPin(): bool
    {
        return ! is_null($this->withdrawal_pin);
    }

    public static function managedRoleOptions(): array
    {
        return [
            self::ROLE_USER,
            self::ROLE_TUTOR,
            self::ROLE_SUPERVISOR,
            self::ROLE_CTO,
            self::ROLE_CEO,
            self::ROLE_PROGRAM_COORDINATOR,
        ];
    }

    public function isTutor(): bool
    {
        return $this->role === self::ROLE_TUTOR;
    }

    public function isExecutive(): bool
    {
        return in_array($this->role, [self::ROLE_CTO, self::ROLE_CEO, self::ROLE_ADMIN_LEGACY], true);
    }

    public function isProgramCoordinator(): bool
    {
        return $this->role === self::ROLE_PROGRAM_COORDINATOR;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    /**
     * Whether this user actually supervises any interns — via a per-intern
     * supervisor override or as the supervisor of a program within a cohort.
     * Capability-based (not role-based), so a tutor or even a plain user who
     * was deliberately assigned as a supervisor qualifies.
     */
    public function supervisesInterns(): bool
    {
        return Internship::query()->forSupervisor($this)->exists();
    }

    /**
     * Who may create "learning ops" work (assignments, schedule items,
     * broadcast notifications) for the audiences they own: tutors and admins
     * as before, plus anyone who actually supervises interns.
     */
    public function canManageLearningOps(): bool
    {
        return $this->isTutor()
            || $this->canAccessAdminPanel()
            || $this->isSupervisor()
            || $this->supervisesInterns();
    }

    /**
     * A plain learner/applicant account (no staff privileges).
     */
    public function isStudent(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Any non-student (staff) account — tutor, supervisor, coordinator, exec.
     */
    public function isStaff(): bool
    {
        return ! $this->isStudent();
    }

    /**
     * Internships where this user is the intern.
     */
    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class, 'user_id');
    }

    /**
     * Internships where this user is the directly-assigned supervisor
     * (does not include cohort-inherited supervision — use Internship::forSupervisor).
     */
    public function supervisedInternships(): HasMany
    {
        return $this->hasMany(Internship::class, 'supervisor_id');
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->isExecutive() || $this->isProgramCoordinator();
    }

    /**
     * Whether this user may grant manual LMS enrollment (by email) for the given course.
     * Admins/coordinators may enroll on draft, pending review, or published courses.
     * Tutors may enroll only on their own published courses.
     */
    public function canManuallyEnrollStudentsInCourse(Course $course): bool
    {
        if ($this->canAccessAdminPanel()) {
            return in_array($course->status, ['published', 'pending_review', 'draft'], true);
        }

        if ($this->isTutor()) {
            return (int) $course->instructor_id === (int) $this->id
                && $course->status === 'published';
        }

        return false;
    }

    public function canManageUsers(): bool
    {
        return $this->isExecutive();
    }

    /*
    |--------------------------------------------------------------------------
    | Traitz Academy Community (TAC)
    |--------------------------------------------------------------------------
    | TAC leadership is a capability layer on top of the staff roles above: a
    | track mentor or school lead may be a plain `user` account and still
    | manage their own corner of the community.
    */

    /**
     * The community leadership posts this account currently holds.
     */
    public function tacLeaderships(): HasMany
    {
        return $this->hasMany(TacLeader::class);
    }

    /** @var \Illuminate\Support\Collection<int, TacLeader>|null */
    protected ?\Illuminate\Support\Collection $tacLeadershipCache = null;

    /**
     * Active leadership posts, memoised per request — authorization checks hit
     * this repeatedly while rendering a single admin page.
     *
     * @return \Illuminate\Support\Collection<int, TacLeader>
     */
    public function activeTacLeaderships(): \Illuminate\Support\Collection
    {
        return $this->tacLeadershipCache ??= TacLeader::query()
            ->active()
            ->where('user_id', $this->id)
            ->get();
    }

    /**
     * May reach the community admin area at all.
     */
    public function canAccessTacAdmin(): bool
    {
        return $this->canAccessAdminPanel() || $this->activeTacLeaderships()->isNotEmpty();
    }

    /**
     * Full authority over TAC — academy executives plus the community's own
     * Lead, Co-Lead, Secretary and Technical Leads.
     */
    public function hasTacExecutiveAuthority(): bool
    {
        return $this->canAccessAdminPanel()
            || $this->activeTacLeaderships()->contains(fn (TacLeader $l) => $l->hasTacExecutiveAuthority());
    }

    /**
     * Track IDs this account mentors.
     *
     * @return array<int, int>
     */
    public function tacManagedTrackIds(): array
    {
        return $this->activeTacLeaderships()
            ->where('role_type', TacLeader::ROLE_TRACK_MENTOR)
            ->pluck('tac_track_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Schools this account leads TAC presence at.
     *
     * @return array<int, string>
     */
    public function tacManagedSchools(): array
    {
        return $this->activeTacLeaderships()
            ->where('role_type', TacLeader::ROLE_SCHOOL_LEAD)
            ->pluck('school')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function tacLeaderIds(): array
    {
        return $this->activeTacLeaderships()->pluck('id')->all();
    }

    public function isTacPartnershipLead(): bool
    {
        return $this->activeTacLeaderships()
            ->contains(fn (TacLeader $l) => $l->role_type === TacLeader::ROLE_PARTNERSHIP_LEAD);
    }

    /**
     * The community member record behind this account, if they have one.
     */
    public function communityMember(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CommunityMember::class);
    }

    /**
     * Whether this user may moderate the given course (view any lesson, manage
     * discussions, etc.). Admins/coordinators may moderate any course; tutors
     * may moderate only courses they own.
     */
    public function canModerateCourse(Course $course): bool
    {
        if ($this->canAccessAdminPanel()) {
            return true;
        }

        return $this->isTutor() && (int) $course->instructor_id === (int) $this->id;
    }

    public function canManagePaymentRecord(Payment $payment): bool
    {
        if ($this->isExecutive()) {
            return true;
        }

        if (! $this->isProgramCoordinator()) {
            return false;
        }

        if (! $payment->manual_entry) {
            return false;
        }

        $collectorId = $payment->recorded_by ?? $payment->updated_by;

        return (int) $collectorId === (int) $this->id;
    }

    public function canManageExpense(Expense $expense): bool
    {
        if ($this->isExecutive()) {
            return true;
        }

        if (! $this->isProgramCoordinator()) {
            return false;
        }

        return (int) $expense->recorded_by === (int) $this->id;
    }
}
