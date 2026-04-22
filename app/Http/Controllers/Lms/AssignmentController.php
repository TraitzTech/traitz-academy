<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssignmentController extends Controller
{
    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return Inertia::render('Admin/Lms/Assignments/Index', [
            'courses' => $this->coursesForAdmin(),
            'assignments' => $this->assignmentRowsForAdmin(),
        ]);
    }

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isTutor(), 403);

        return Inertia::render('Tutor/Assignments/Index', [
            'courses' => $this->coursesForTutor((int) $user->id),
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

        $assignments = Assignment::query()
            ->where(function ($query) use ($userId, $enrolledCourseIds): void {
                $query->where(function ($courseAudience) use ($enrolledCourseIds): void {
                    $courseAudience
                        ->where('audience', 'all_course_students')
                        ->whereIn('course_id', $enrolledCourseIds);
                })->orWhere(function ($selectedAudience) use ($userId): void {
                    $selectedAudience
                        ->where('audience', 'selected_students')
                        ->whereHas('selectedStudents', fn ($selected) => $selected->where('users.id', $userId));
                });
            })
            ->with(['course:id,title', 'creator:id,name'])
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
        abort_unless($user?->isTutor(), 403);

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
            'course_id' => ['required', 'integer', 'exists:courses,id'],
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
        $course = Course::query()->findOrFail((int) $payload['course_id']);

        if (! $isAdmin) {
            abort_unless((int) $course->instructor_id === (int) $tutorId, 403);
        }

        $allowedStudentIds = Enrollment::query()
            ->where('course_id', $course->id)
            ->where('access_status', '!=', 'revoked')
            ->pluck('user_id')
            ->unique()
            ->values();

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
            'course_id' => $course->id,
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
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}>
     */
    private function coursesForAdmin(): array
    {
        return Course::query()
            ->select('id', 'title')
            ->with(['enrollments:id,course_id,user_id,access_status', 'enrollments.user:id,name,email,role'])
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course) => $this->mapCourseWithStudents($course))
            ->all();
    }

    /**
     * @return array<int, array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}>
     */
    private function coursesForTutor(int $tutorId): array
    {
        return Course::query()
            ->where('instructor_id', $tutorId)
            ->select('id', 'title')
            ->with(['enrollments:id,course_id,user_id,access_status', 'enrollments.user:id,name,email,role'])
            ->orderBy('title')
            ->get()
            ->map(fn (Course $course) => $this->mapCourseWithStudents($course))
            ->all();
    }

    /**
     * @return array{id:int,title:string,student_count:int,students:array<int,array{id:int,name:string,email:string}>}
     */
    private function mapCourseWithStudents(Course $course): array
    {
        $students = $course->enrollments
            ->filter(fn (Enrollment $enrollment) => $enrollment->access_status !== 'revoked')
            ->map(fn (Enrollment $enrollment) => $enrollment->user)
            ->filter(fn ($user) => $user && $user->role === 'user')
            ->unique('id')
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values()
            ->all();

        return [
            'id' => $course->id,
            'title' => $course->title,
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
            ->with(['course:id,title', 'creator:id,name', 'selectedStudents:id'])
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
            ->with(['course:id,title', 'creator:id,name', 'selectedStudents:id'])
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
        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'instructions' => $assignment->instructions,
            'audience' => $assignment->audience,
            'course' => [
                'id' => $assignment->course?->id,
                'title' => $assignment->course?->title,
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
