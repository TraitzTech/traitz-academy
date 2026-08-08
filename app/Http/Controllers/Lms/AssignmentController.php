<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Internship;
use App\Models\Program;
use App\Services\LearningAudienceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function __construct(private readonly LearningAudienceService $audience) {}

    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return Inertia::render('Admin/Lms/Assignments/Index', [
            ...$this->audience->allGroups(),
            'assignments' => $this->assignmentRowsForAdmin(),
        ]);
    }

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->canManageLearningOps(), 403);

        return Inertia::render('Tutor/Assignments/Index', [
            ...$this->audience->managedGroupsFor($user),
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
        abort_unless($user?->canManageLearningOps(), 403);

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
        $attachable = $this->audience->resolveAttachable((string) $payload['attachable_type'], (int) $payload['attachable_id']);

        if (! $isAdmin) {
            abort_unless($this->audience->userCanManage($attachable, (int) $tutorId), 403);
        }

        $allowedStudentIds = $this->audience->manageableStudentIds($attachable, $tutorId, $isAdmin);

        $audienceValue = (string) $payload['audience'];

        $selectedIds = collect($payload['student_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($audienceValue === 'selected_students') {
            $selectedIds = $selectedIds
                ->filter(fn ($id) => $allowedStudentIds->contains($id))
                ->values();

            abort_if($selectedIds->isEmpty(), 422, 'Please select at least one valid student.');
        } elseif (! $isAdmin && $attachable instanceof Program) {
            // A supervisor's program roster is scoped to the cohorts they
            // supervise, but "everyone in the program" is resolved student-side
            // by program membership alone — which would reach interns in other
            // cohorts. Pin the audience to the supervisor's exact interns.
            $audienceValue = 'selected_students';
            $selectedIds = $allowedStudentIds->values();

            abort_if($selectedIds->isEmpty(), 422, 'You have no interns to assign in this program.');
        } else {
            $selectedIds = collect();
        }

        $attachmentPath = $request->file('attachment')?->store('assignments', 'public');

        $assignment = Assignment::query()->create([
            'course_id' => $attachable instanceof Course ? $attachable->id : null,
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->id,
            'created_by' => (int) $request->user()->id,
            'title' => (string) $payload['title'],
            'instructions' => (string) $payload['instructions'],
            'audience' => $audienceValue,
            'attachment_path' => $attachmentPath,
            'due_at' => $payload['due_at'] ?? null,
        ]);

        $assignment->selectedStudents()->sync($selectedIds->all());

        return $assignment;
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
                'type' => $this->audience->typeKey($attachable),
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
