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
use App\Services\LearningAudienceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(private readonly LearningAudienceService $audience) {}

    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return Inertia::render('Admin/Lms/Schedules/Index', [
            ...$this->audience->allGroups(),
            'schedules' => $this->scheduleRowsForAdmin(),
        ]);
    }

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->canManageLearningOps(), 403);

        return Inertia::render('Tutor/Schedules/Index', [
            ...$this->audience->managedGroupsFor($user),
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
        abort_unless($user?->canManageLearningOps(), 403);

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
        abort_unless($user?->canManageLearningOps(), 403);
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
        abort_unless($user?->canManageLearningOps(), 403);
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

        $allowedStudentIds = $attachable ? $this->audience->studentIds($attachable) : collect();

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

        $allowedStudentIds = $attachable ? $this->audience->studentIds($attachable) : collect();

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

        $attachable = $this->audience->resolveAttachable((string) $payload['attachable_type'], (int) $payload['attachable_id']);

        if (! $isAdmin) {
            abort_unless($this->audience->userCanManage($attachable, (int) $userId), 403);
        }

        return $attachable;
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
                'type' => $this->audience->typeKey($attachable),
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
                ? $this->audience->studentIds($schedule->attachable)
                : collect(),
            default => $schedule->selectedStudents()->pluck('users.id'),
        };

        User::query()
            ->whereIn('id', $studentIds)
            ->each(fn (User $user) => $user->notify(new SchedulePublishedNotification($schedule)));
    }
}
