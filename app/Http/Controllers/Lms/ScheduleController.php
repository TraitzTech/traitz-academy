<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Internship;
use App\Models\LmsSchedule;
use App\Models\Program;
use App\Models\User;
use App\Notifications\Lms\SchedulePublishedNotification;
use App\Services\RosterResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    /**
     * Attachable type keys accepted from the client, mapped to their model
     * class. The DB `audience` enum keeps its historical value
     * "course_students" — reinterpreted here as "attachable_students" so no
     * enum-widening migration is needed.
     */
    private const ATTACHABLE_TYPES = [
        'course' => Course::class,
        'cohort' => Cohort::class,
        'program' => Program::class,
    ];

    public function __construct(private readonly RosterResolver $rosterResolver) {}

    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return Inertia::render('Admin/Lms/Schedules/Index', [
            'courses' => $this->coursesWithRoster(Course::query()),
            'cohorts' => $this->cohortsWithRoster(Cohort::query()),
            'programs' => $this->programsWithRoster(Program::query()),
            'schedules' => $this->scheduleRowsForAdmin(),
        ]);
    }

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isTutor() || $user?->isSupervisor(), 403);

        return Inertia::render('Tutor/Schedules/Index', [
            'courses' => $this->coursesWithRoster(Course::query()->where('instructor_id', $user->id)),
            'cohorts' => $this->cohortsWithRoster($this->cohortsSupervisedBy((int) $user->id)),
            'programs' => $this->programsWithRoster($this->programsSupervisedBy((int) $user->id)),
            'schedules' => $this->scheduleRowsForTutor((int) $user->id),
        ]);
    }

    public function studentIndex(Request $request): Response
    {
        $userId = (int) $request->user()->id;
        $enrolledCourseIds = Enrollment::query()
            ->where('user_id', $userId)
            ->where('access_status', '!=', 'revoked')
            ->pluck('course_id');

        $internships = Internship::query()->where('user_id', $userId)->get(['cohort_id', 'program_id']);
        $cohortIds = $internships->pluck('cohort_id')->filter()->unique();
        $programIds = $internships->pluck('program_id')->filter()->unique();

        $schedules = LmsSchedule::query()
            ->where(function ($query) use ($userId, $enrolledCourseIds, $cohortIds, $programIds): void {
                $query->where('audience', 'all_students')
                    ->orWhere(function ($groupAudience) use ($enrolledCourseIds, $cohortIds, $programIds): void {
                        $groupAudience
                            ->where('audience', 'course_students')
                            ->where(function ($attachables) use ($enrolledCourseIds, $cohortIds, $programIds): void {
                                $attachables
                                    ->where(function ($q) use ($enrolledCourseIds) {
                                        $q->where('attachable_type', Course::class)->whereIn('attachable_id', $enrolledCourseIds);
                                    })
                                    ->orWhere(function ($q) use ($cohortIds) {
                                        $q->where('attachable_type', Cohort::class)->whereIn('attachable_id', $cohortIds);
                                    })
                                    ->orWhere(function ($q) use ($programIds) {
                                        $q->where('attachable_type', Program::class)->whereIn('attachable_id', $programIds);
                                    });
                            });
                    })
                    ->orWhere(function ($selectedAudience) use ($userId): void {
                        $selectedAudience
                            ->where('audience', 'selected_students')
                            ->whereHas('selectedStudents', fn ($selected) => $selected->where('users.id', $userId));
                    });
            })
            ->with(['attachable', 'creator:id,name'])
            ->orderBy('starts_at')
            ->limit(200)
            ->get()
            ->map(fn (LmsSchedule $schedule) => $this->mapScheduleRow($schedule));

        return Inertia::render('Lms/Schedules/Index', [
            'schedules' => $schedules,
        ]);
    }

    public function adminStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        $payload = $this->validatePayload($request, true);
        $schedule = $this->storeSchedule($request, $payload, true, null);

        return back()->with('success', "Schedule \"{$schedule->title}\" created.");
    }

    public function tutorStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isTutor() || $user?->isSupervisor(), 403);

        $payload = $this->validatePayload($request, false);
        $schedule = $this->storeSchedule($request, $payload, false, (int) $user->id);

        return back()->with('success', "Schedule \"{$schedule->title}\" created.");
    }

    public function adminUpdate(Request $request, LmsSchedule $schedule): RedirectResponse
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        $payload = $this->validatePayload($request, true);
        $updated = $this->updateSchedule($request, $schedule, $payload, true, null);

        return back()->with('success', "Schedule \"{$updated->title}\" updated.");
    }

    public function tutorUpdate(Request $request, LmsSchedule $schedule): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isTutor() || $user?->isSupervisor(), 403);
        abort_unless((int) $schedule->created_by === (int) $user->id, 403);

        $payload = $this->validatePayload($request, false);
        $updated = $this->updateSchedule($request, $schedule, $payload, false, (int) $user->id);

        return back()->with('success', "Schedule \"{$updated->title}\" updated.");
    }

    public function adminDestroy(Request $request, LmsSchedule $schedule): RedirectResponse
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);
        $schedule->delete();

        return back()->with('success', 'Schedule deleted.');
    }

    public function tutorDestroy(Request $request, LmsSchedule $schedule): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isTutor() || $user?->isSupervisor(), 403);
        abort_unless((int) $schedule->created_by === (int) $user->id, 403);

        $schedule->delete();

        return back()->with('success', 'Schedule deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $isAdmin): array
    {
        $audiences = ['course_students', 'selected_students'];
        if ($isAdmin) {
            $audiences[] = 'all_students';
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'attachable_type' => ['nullable', 'in:course,cohort,program'],
            'attachable_id' => ['nullable', 'integer'],
            'audience' => ['required', 'string', 'in:'.implode(',', $audiences)],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeSchedule(Request $request, array $payload, bool $isAdmin, ?int $userId): LmsSchedule
    {
        $attachable = $this->resolveAttachable($payload, $isAdmin, $userId);

        if (! $isAdmin && $payload['audience'] === 'all_students') {
            abort(403);
        }

        if (in_array($payload['audience'], ['course_students', 'selected_students'], true) && ! $attachable) {
            abort(422, 'A course, cohort, or program is required for this audience.');
        }

        $allowedStudentIds = $attachable ? $this->rosterResolver->resolveStudentIds($attachable)->unique()->values() : collect();

        $selectedIds = collect($payload['student_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        if ($payload['audience'] === 'selected_students') {
            $selectedIds = $selectedIds->filter(fn ($id) => $allowedStudentIds->contains($id))->values();
            abort_if($selectedIds->isEmpty(), 422, 'Please select at least one valid student.');
        } else {
            $selectedIds = collect();
        }

        $schedule = LmsSchedule::query()->create([
            'created_by' => (int) $request->user()->id,
            'course_id' => $attachable instanceof Course ? $attachable->id : null,
            'attachable_type' => $attachable ? get_class($attachable) : null,
            'attachable_id' => $attachable?->id,
            'title' => (string) $payload['title'],
            'description' => $payload['description'] ?? null,
            'audience' => (string) $payload['audience'],
            'location' => $payload['location'] ?? null,
            'starts_at' => (string) $payload['starts_at'],
            'ends_at' => $payload['ends_at'] ?? null,
        ]);

        $schedule->selectedStudents()->sync($selectedIds->all());
        $this->notifyAudience($schedule);

        return $schedule;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateSchedule(
        Request $request,
        LmsSchedule $schedule,
        array $payload,
        bool $isAdmin,
        ?int $userId
    ): LmsSchedule {
        $attachable = $this->resolveAttachable($payload, $isAdmin, $userId);

        if (! $isAdmin && $payload['audience'] === 'all_students') {
            abort(403);
        }

        if (in_array($payload['audience'], ['course_students', 'selected_students'], true) && ! $attachable) {
            abort(422, 'A course, cohort, or program is required for this audience.');
        }

        $allowedStudentIds = $attachable ? $this->rosterResolver->resolveStudentIds($attachable)->unique()->values() : collect();

        $selectedIds = collect($payload['student_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        if ($payload['audience'] === 'selected_students') {
            $selectedIds = $selectedIds->filter(fn ($id) => $allowedStudentIds->contains($id))->values();
            abort_if($selectedIds->isEmpty(), 422, 'Please select at least one valid student.');
        } else {
            $selectedIds = collect();
        }

        $schedule->update([
            'course_id' => $attachable instanceof Course ? $attachable->id : null,
            'attachable_type' => $attachable ? get_class($attachable) : null,
            'attachable_id' => $attachable?->id,
            'title' => (string) $payload['title'],
            'description' => $payload['description'] ?? null,
            'audience' => (string) $payload['audience'],
            'location' => $payload['location'] ?? null,
            'starts_at' => (string) $payload['starts_at'],
            'ends_at' => $payload['ends_at'] ?? null,
        ]);

        $schedule->selectedStudents()->sync($selectedIds->all());
        $this->notifyAudience($schedule);

        return $schedule->fresh(['attachable', 'creator:id,name', 'selectedStudents:id']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveAttachable(array $payload, bool $isAdmin, ?int $userId): ?Model
    {
        if (empty($payload['attachable_type']) || empty($payload['attachable_id'])) {
            return null;
        }

        $modelClass = self::ATTACHABLE_TYPES[$payload['attachable_type']];
        $attachable = $modelClass::query()->findOrFail((int) $payload['attachable_id']);

        if (! $isAdmin) {
            abort_unless($this->userCanManage($attachable, (int) $userId), 403);
        }

        return $attachable;
    }

    private function userCanManage(Model $attachable, int $userId): bool
    {
        return match (true) {
            $attachable instanceof Course => (int) $attachable->instructor_id === $userId,
            $attachable instanceof Cohort => $attachable->programs()->wherePivot('supervisor_id', $userId)->exists(),
            $attachable instanceof Program => $attachable->cohorts()->wherePivot('supervisor_id', $userId)->exists(),
            default => false,
        };
    }

    private function cohortsSupervisedBy(int $userId): Builder
    {
        return Cohort::query()->whereHas('programs', fn ($q) => $q->wherePivot('supervisor_id', $userId));
    }

    private function programsSupervisedBy(int $userId): Builder
    {
        return Program::query()->whereHas('cohorts', fn ($q) => $q->wherePivot('supervisor_id', $userId));
    }

    /**
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}>
     */
    private function coursesWithRoster(Builder $query): array
    {
        return $query
            ->select('id', 'title')
            ->with(['enrollments:id,course_id,user_id,access_status', 'enrollments.user:id,name,email,role'])
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'student_count' => $course->enrollments->where('access_status', '!=', 'revoked')->count(),
                'students' => $course->enrollments
                    ->filter(fn (Enrollment $e) => $e->access_status !== 'revoked')
                    ->map(fn (Enrollment $e) => $e->user)
                    ->filter(fn ($u) => $u && $u->role === 'user')
                    ->unique('id')
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}>
     */
    private function cohortsWithRoster(Builder $query): array
    {
        return $query
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (Cohort $cohort) => $this->attachableRoster($cohort, $cohort->name))
            ->all();
    }

    /**
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}>
     */
    private function programsWithRoster(Builder $query): array
    {
        return $query
            ->select('id', 'title')
            ->orderBy('title')
            ->get()
            ->map(fn (Program $program) => $this->attachableRoster($program, $program->title))
            ->all();
    }

    /**
     * @return array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}
     */
    private function attachableRoster(Model $attachable, string $title): array
    {
        $studentIds = $this->rosterResolver->resolveStudentIds($attachable)->unique()->values();

        $students = User::query()
            ->whereIn('id', $studentIds)
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
            ->all();

        return [
            'id' => $attachable->id,
            'title' => $title,
            'student_count' => count($students),
            'students' => $students,
        ];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function scheduleRowsForAdmin(): array
    {
        return LmsSchedule::query()
            ->with(['attachable', 'creator:id,name', 'selectedStudents:id'])
            ->orderByDesc('starts_at')
            ->limit(150)
            ->get()
            ->map(fn (LmsSchedule $schedule) => $this->mapScheduleRow($schedule))
            ->all();
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function scheduleRowsForTutor(int $tutorId): array
    {
        return LmsSchedule::query()
            ->where('created_by', $tutorId)
            ->with(['attachable', 'creator:id,name', 'selectedStudents:id'])
            ->orderByDesc('starts_at')
            ->limit(150)
            ->get()
            ->map(fn (LmsSchedule $schedule) => $this->mapScheduleRow($schedule))
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    private function mapScheduleRow(LmsSchedule $schedule): array
    {
        $attachable = $schedule->attachable;

        return [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'description' => $schedule->description,
            'audience' => $schedule->audience,
            'location' => $schedule->location,
            'starts_at' => $schedule->starts_at?->toIso8601String(),
            'ends_at' => $schedule->ends_at?->toIso8601String(),
            'created_at' => $schedule->created_at?->toIso8601String(),
            'created_by' => $schedule->creator?->name,
            'attachable' => [
                'type' => $attachable ? array_search(get_class($attachable), self::ATTACHABLE_TYPES, true) : null,
                'id' => $attachable?->id,
                'title' => $attachable?->title ?? $attachable?->name,
            ],
            'selected_students_count' => $schedule->audience === 'selected_students'
                ? $schedule->selectedStudents->count()
                : null,
        ];
    }

    private function notifyAudience(LmsSchedule $schedule): void
    {
        $studentIds = match ($schedule->audience) {
            'all_students' => User::query()->where('role', 'user')->pluck('id'),
            'course_students' => $schedule->attachable
                ? $this->rosterResolver->resolveStudentIds($schedule->attachable)->unique()->values()
                : collect(),
            default => $schedule->selectedStudents()->pluck('users.id'),
        };

        User::query()
            ->whereIn('id', $studentIds)
            ->each(fn (User $user) => $user->notify(new SchedulePublishedNotification($schedule)));
    }
}
