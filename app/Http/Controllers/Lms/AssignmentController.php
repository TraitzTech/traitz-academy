<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;
use App\Services\RosterResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    /** Attachable type keys accepted from the client, mapped to their model class. */
    private const ATTACHABLE_TYPES = [
        'course' => Course::class,
        'cohort' => Cohort::class,
        'program' => Program::class,
    ];

    public function __construct(private readonly RosterResolver $rosterResolver) {}

    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return Inertia::render('Admin/Lms/Assignments/Index', [
            'courses' => $this->coursesWithRoster(Course::query()),
            'cohorts' => $this->cohortsWithRoster(Cohort::query()),
            'programs' => $this->programsWithRoster(Program::query()),
            'assignments' => $this->assignmentRowsForAdmin(),
        ]);
    }

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isTutor() || $user?->isSupervisor(), 403);

        return Inertia::render('Tutor/Assignments/Index', [
            'courses' => $this->coursesWithRoster(Course::query()->where('instructor_id', $user->id)),
            'cohorts' => $this->cohortsWithRoster($this->cohortsSupervisedBy((int) $user->id)),
            'programs' => $this->programsWithRoster($this->programsSupervisedBy((int) $user->id)),
            'assignments' => $this->assignmentRowsForTutor((int) $user->id),
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

        $assignments = Assignment::query()
            ->where(function ($query) use ($userId, $enrolledCourseIds, $cohortIds, $programIds): void {
                $query->where(function ($groupAudience) use ($enrolledCourseIds, $cohortIds, $programIds): void {
                    $groupAudience
                        ->where('audience', 'all_course_students')
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
                })->orWhere(function ($selectedAudience) use ($userId): void {
                    $selectedAudience
                        ->where('audience', 'selected_students')
                        ->whereHas('selectedStudents', fn ($selected) => $selected->where('users.id', $userId));
                });
            })
            ->with(['attachable', 'creator:id,name'])
            ->latest()
            ->get()
            ->map(fn (Assignment $assignment) => $this->mapAssignmentRow($assignment));

        return Inertia::render('Lms/Assignments/Index', [
            'assignments' => $assignments,
        ]);
    }

    public function adminStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        $payload = $this->validatePayload($request);
        $assignment = $this->storeAssignment($request, $payload, null, true);

        return back()->with('success', "Assignment \"{$assignment->title}\" created.");
    }

    public function tutorStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isTutor() || $user?->isSupervisor(), 403);

        $payload = $this->validatePayload($request);
        $assignment = $this->storeAssignment($request, $payload, (int) $user->id, false);

        return back()->with('success', "Assignment \"{$assignment->title}\" created.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'attachable_type' => ['required', 'in:course,cohort,program'],
            'attachable_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string', 'max:30000'],
            'audience' => ['required', 'in:all_course_students,selected_students'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeAssignment(Request $request, array $payload, ?int $tutorId, bool $isAdmin): Assignment
    {
        $modelClass = self::ATTACHABLE_TYPES[$payload['attachable_type']];
        $attachable = $modelClass::query()->findOrFail((int) $payload['attachable_id']);

        if (! $isAdmin) {
            abort_unless($this->userCanManage($attachable, (int) $tutorId), 403);
        }

        $allowedStudentIds = $this->rosterResolver->resolveStudentIds($attachable)->unique()->values();

        $selectedIds = collect($payload['student_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($payload['audience'] === 'selected_students') {
            $selectedIds = $selectedIds
                ->filter(fn ($id) => $allowedStudentIds->contains($id))
                ->values();

            abort_if($selectedIds->isEmpty(), 422, 'Please select at least one valid student.');
        } else {
            $selectedIds = collect();
        }

        $attachmentPath = $request->file('attachment')?->store('assignments', 'public');

        $assignment = Assignment::query()->create([
            'course_id' => $attachable instanceof Course ? $attachable->id : null,
            'attachable_type' => $modelClass,
            'attachable_id' => $attachable->id,
            'created_by' => (int) $request->user()->id,
            'title' => (string) $payload['title'],
            'instructions' => (string) $payload['instructions'],
            'audience' => (string) $payload['audience'],
            'attachment_path' => $attachmentPath,
            'due_at' => $payload['due_at'] ?? null,
        ]);

        $assignment->selectedStudents()->sync($selectedIds->all());

        return $assignment;
    }

    /**
     * Whether the given tutor/supervisor may create work for this attachable.
     */
    private function userCanManage(Model $attachable, int $userId): bool
    {
        return match (true) {
            $attachable instanceof Course => (int) $attachable->instructor_id === $userId,
            $attachable instanceof Cohort => $attachable->programs()->wherePivot('supervisor_id', $userId)->exists(),
            $attachable instanceof Program => $attachable->cohorts()->wherePivot('supervisor_id', $userId)->exists(),
            default => false,
        };
    }

    private function cohortsSupervisedBy(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return Cohort::query()->whereHas('programs', fn ($q) => $q->wherePivot('supervisor_id', $userId));
    }

    private function programsSupervisedBy(int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return Program::query()->whereHas('cohorts', fn ($q) => $q->wherePivot('supervisor_id', $userId));
    }

    /**
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}>
     */
    private function coursesWithRoster(\Illuminate\Database\Eloquent\Builder $query): array
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
    private function cohortsWithRoster(\Illuminate\Database\Eloquent\Builder $query): array
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
    private function programsWithRoster(\Illuminate\Database\Eloquent\Builder $query): array
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
    private function assignmentRowsForAdmin(): array
    {
        return Assignment::query()
            ->with(['attachable', 'creator:id,name', 'selectedStudents:id'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Assignment $assignment) => $this->mapAssignmentRow($assignment))
            ->all();
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function assignmentRowsForTutor(int $tutorId): array
    {
        return Assignment::query()
            ->where('created_by', $tutorId)
            ->with(['attachable', 'creator:id,name', 'selectedStudents:id'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Assignment $assignment) => $this->mapAssignmentRow($assignment))
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    private function mapAssignmentRow(Assignment $assignment): array
    {
        $attachable = $assignment->attachable;

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'instructions' => $assignment->instructions,
            'audience' => $assignment->audience,
            'attachable' => [
                'type' => $attachable ? array_search(get_class($attachable), self::ATTACHABLE_TYPES, true) : null,
                'id' => $attachable?->id,
                'title' => $attachable?->title ?? $attachable?->name,
            ],
            'created_by' => $assignment->creator?->name,
            'due_at' => $assignment->due_at?->toIso8601String(),
            'attachment_url' => $assignment->attachment_path ? asset('storage/'.$assignment->attachment_path) : null,
            'selected_students_count' => $assignment->audience === 'selected_students'
                ? $assignment->selectedStudents->count()
                : null,
            'created_at' => $assignment->created_at?->toIso8601String(),
        ];
    }
}
