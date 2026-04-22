<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LmsSchedule;
use App\Models\User;
use App\Notifications\Lms\SchedulePublishedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return Inertia::render('Admin/Lms/Schedules/Index', [
            'courses' => $this->coursesForAdmin(),
            'schedules' => $this->scheduleRowsForAdmin(),
        ]);
    }

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isTutor(), 403);

        return Inertia::render('Tutor/Schedules/Index', [
            'courses' => $this->coursesForTutor((int) $user->id),
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

        $schedules = LmsSchedule::query()
            ->where(function ($query) use ($userId, $enrolledCourseIds): void {
                $query->where('audience', 'all_students')
                    ->orWhere(function ($courseAudience) use ($enrolledCourseIds): void {
                        $courseAudience
                            ->where('audience', 'course_students')
                            ->whereIn('course_id', $enrolledCourseIds);
                    })
                    ->orWhere(function ($selectedAudience) use ($userId): void {
                        $selectedAudience
                            ->where('audience', 'selected_students')
                            ->whereHas('selectedStudents', fn ($selected) => $selected->where('users.id', $userId));
                    });
            })
            ->with(['course:id,title', 'creator:id,name'])
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
        abort_unless($user?->isTutor(), 403);

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
        abort_unless($user?->isTutor(), 403);
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
        abort_unless($user?->isTutor(), 403);
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
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
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
    private function storeSchedule(Request $request, array $payload, bool $isAdmin, ?int $tutorId): LmsSchedule
    {
        $course = null;
        if (! empty($payload['course_id'])) {
            $course = Course::query()->findOrFail((int) $payload['course_id']);
            if (! $isAdmin) {
                abort_unless((int) $course->instructor_id === (int) $tutorId, 403);
            }
        }

        if (! $isAdmin && $payload['audience'] === 'all_students') {
            abort(403);
        }

        if (in_array($payload['audience'], ['course_students', 'selected_students'], true) && ! $course) {
            abort(422, 'A course is required for this audience.');
        }

        $allowedStudentIds = collect();
        if ($course) {
            $allowedStudentIds = Enrollment::query()
                ->where('course_id', $course->id)
                ->where('access_status', '!=', 'revoked')
                ->pluck('user_id')
                ->unique()
                ->values();
        }

        $selectedIds = collect($payload['student_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        if ($payload['audience'] === 'selected_students') {
            $selectedIds = $selectedIds->filter(fn ($id) => $allowedStudentIds->contains($id))->values();
            abort_if($selectedIds->isEmpty(), 422, 'Please select at least one valid student.');
        } else {
            $selectedIds = collect();
        }

        $schedule = LmsSchedule::query()->create([
            'created_by' => (int) $request->user()->id,
            'course_id' => $course?->id,
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
        ?int $tutorId
    ): LmsSchedule {
        $course = null;
        if (! empty($payload['course_id'])) {
            $course = Course::query()->findOrFail((int) $payload['course_id']);
            if (! $isAdmin) {
                abort_unless((int) $course->instructor_id === (int) $tutorId, 403);
            }
        }

        if (! $isAdmin && $payload['audience'] === 'all_students') {
            abort(403);
        }

        if (in_array($payload['audience'], ['course_students', 'selected_students'], true) && ! $course) {
            abort(422, 'A course is required for this audience.');
        }

        $allowedStudentIds = collect();
        if ($course) {
            $allowedStudentIds = Enrollment::query()
                ->where('course_id', $course->id)
                ->where('access_status', '!=', 'revoked')
                ->pluck('user_id')
                ->unique()
                ->values();
        }

        $selectedIds = collect($payload['student_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        if ($payload['audience'] === 'selected_students') {
            $selectedIds = $selectedIds->filter(fn ($id) => $allowedStudentIds->contains($id))->values();
            abort_if($selectedIds->isEmpty(), 422, 'Please select at least one valid student.');
        } else {
            $selectedIds = collect();
        }

        $schedule->update([
            'course_id' => $course?->id,
            'title' => (string) $payload['title'],
            'description' => $payload['description'] ?? null,
            'audience' => (string) $payload['audience'],
            'location' => $payload['location'] ?? null,
            'starts_at' => (string) $payload['starts_at'],
            'ends_at' => $payload['ends_at'] ?? null,
        ]);

        $schedule->selectedStudents()->sync($selectedIds->all());
        $this->notifyAudience($schedule);

        return $schedule->fresh(['course:id,title', 'creator:id,name', 'selectedStudents:id']);
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
    private function scheduleRowsForAdmin(): array
    {
        return LmsSchedule::query()
            ->with(['course:id,title', 'creator:id,name', 'selectedStudents:id'])
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
            ->with(['course:id,title', 'creator:id,name', 'selectedStudents:id'])
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
            'course' => [
                'id' => $schedule->course?->id,
                'title' => $schedule->course?->title,
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
            'course_students' => Enrollment::query()
                ->where('course_id', $schedule->course_id)
                ->where('access_status', '!=', 'revoked')
                ->pluck('user_id')
                ->unique()
                ->values(),
            default => $schedule->selectedStudents()->pluck('users.id'),
        };

        User::query()
            ->whereIn('id', $studentIds)
            ->each(fn (User $user) => $user->notify(new SchedulePublishedNotification($schedule)));
    }
}
