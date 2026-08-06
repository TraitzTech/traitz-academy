<?php

namespace App\Http\Controllers\Tutor;

use App\Concerns\SyncsLiveClassTargets;
use App\Http\Controllers\Concerns\GeneratesLiveClassMeeting;
use App\Http\Controllers\Controller;
use App\Jobs\UploadLiveClassRecordingToYouTube;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\LiveClassRecording;
use App\Models\Program;
use App\Models\User;
use App\Notifications\Lms\LiveClassScheduledNotification;
use App\Support\LiveClass\GoogleMeetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LiveClassController extends Controller
{
    use GeneratesLiveClassMeeting;
    use SyncsLiveClassTargets;

    public function index(Request $request): Response
    {
        $classes = LiveClass::query()
            ->where('tutor_id', $request->user()->id)
            ->with(['courses:id,title'])
            ->latest('start_time')
            ->get();

        return Inertia::render('Tutor/LiveClasses/Index', [
            'classes' => $classes,
        ]);
    }

    public function create(Request $request): Response
    {
        $userId = (int) $request->user()->id;

        return Inertia::render('Tutor/LiveClasses/Create', [
            'courses' => $this->ownedCourses($userId),
            'cohorts' => $this->supervisedCohorts($userId),
            'programs' => $this->supervisedPrograms($userId),
            'students' => $this->reachableStudents($userId),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'duration' => ['required', 'integer', 'min:5', 'max:600'],
            'access_type' => ['required', 'in:course,custom'],
            'meeting_url' => ['nullable', 'url', 'max:2048'],
            'targets' => ['array'],
            'targets.*.type' => ['required_with:targets', 'in:course,cohort,program'],
            'targets.*.id' => ['required_with:targets', 'integer'],
            'student_ids' => ['array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $liveClass = LiveClass::query()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'tutor_id' => (int) $request->user()->id,
            'created_by' => (int) $request->user()->id,
            'start_time' => $validated['start_time'],
            'duration' => $validated['duration'],
            'room_name' => 'class_'.Str::random(10),
            'access_type' => $validated['access_type'],
        ]);

        $this->ensureMeetingLink($liveClass, $validated['meeting_url'] ?? null);
        $this->syncOwnedAudience($request, $liveClass, $validated);
        $this->notifyAudience($liveClass);

        return redirect()->route('tutor.live-classes.index')->with('success', 'Live class scheduled.');
    }

    public function show(Request $request, LiveClass $liveClass): Response
    {
        $this->authorizeTutor($request, $liveClass);

        $liveClass->load([
            'courses:id,title',
            'students:id,name,email',
            'recordings',
            'attendance.student:id,name,email',
        ]);

        return Inertia::render('Tutor/LiveClasses/Show', [
            'liveClass' => $liveClass,
            'targets' => $this->targetRowsForDisplay($liveClass),
        ]);
    }

    public function edit(Request $request, LiveClass $liveClass): Response
    {
        $this->authorizeTutor($request, $liveClass);

        $userId = (int) $request->user()->id;

        $liveClass->load(['courses:id,title', 'students:id,name,email']);

        return Inertia::render('Tutor/LiveClasses/Edit', [
            'liveClass' => $liveClass,
            'targets' => $this->targetRowsForDisplay($liveClass),
            'courses' => $this->ownedCourses($userId),
            'cohorts' => $this->supervisedCohorts($userId),
            'programs' => $this->supervisedPrograms($userId),
            'students' => $this->reachableStudents($userId),
        ]);
    }

    public function update(Request $request, LiveClass $liveClass): RedirectResponse
    {
        $this->authorizeTutor($request, $liveClass);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'duration' => ['required', 'integer', 'min:5', 'max:600'],
            'access_type' => ['required', 'in:course,custom'],
            'meeting_url' => ['nullable', 'url', 'max:2048'],
            'targets' => ['array'],
            'targets.*.type' => ['required_with:targets', 'in:course,cohort,program'],
            'targets.*.id' => ['required_with:targets', 'integer'],
            'student_ids' => ['array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $liveClass->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_time' => $validated['start_time'],
            'duration' => $validated['duration'],
            'access_type' => $validated['access_type'],
        ]);

        $this->ensureMeetingLink($liveClass, $validated['meeting_url'] ?? null);
        $this->syncOwnedAudience($request, $liveClass, $validated);

        return back()->with('success', 'Live class updated.');
    }

    public function destroy(Request $request, LiveClass $liveClass): RedirectResponse
    {
        $this->authorizeTutor($request, $liveClass);
        app(GoogleMeetService::class)->deleteMeeting($liveClass->meeting_event_id);
        $liveClass->delete();

        return back()->with('success', 'Live class deleted.');
    }

    public function addRecording(Request $request, LiveClass $liveClass): RedirectResponse
    {
        $this->authorizeTutor($request, $liveClass);

        $validated = $request->validate([
            'recording_file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm', 'max:1024000'],
        ]);

        $path = $validated['recording_file']->store('live-class-recordings/'.$liveClass->id, 'public');
        $recording = LiveClassRecording::query()->create([
            'live_class_id' => $liveClass->id,
            'file_path' => $path,
            'status' => 'processing',
        ]);

        UploadLiveClassRecordingToYouTube::dispatch($recording->id);

        return back()->with('success', 'Recording upload queued.');
    }

    private function authorizeTutor(Request $request, LiveClass $liveClass): void
    {
        abort_unless((int) $liveClass->tutor_id === (int) $request->user()->id, 403);
    }

    /**
     * Same as syncTargets(), but drops any target the tutor doesn't own/supervise.
     */
    private function syncOwnedAudience(Request $request, LiveClass $liveClass, array $validated): void
    {
        $userId = (int) $request->user()->id;

        if ($liveClass->access_type === 'course') {
            $allowedCourseIds = $this->ownedCourses($userId)->pluck('id')->all();
            $allowedCohortIds = $this->supervisedCohorts($userId)->pluck('id')->all();
            $allowedProgramIds = $this->supervisedPrograms($userId)->pluck('id')->all();

            $targets = collect($validated['targets'] ?? [])
                ->filter(function ($target) use ($allowedCourseIds, $allowedCohortIds, $allowedProgramIds) {
                    return match ($target['type'] ?? null) {
                        'course' => in_array((int) $target['id'], $allowedCourseIds, true),
                        'cohort' => in_array((int) $target['id'], $allowedCohortIds, true),
                        'program' => in_array((int) $target['id'], $allowedProgramIds, true),
                        default => false,
                    };
                })
                ->values()
                ->all();

            $this->syncTargets($liveClass, $targets);
            $liveClass->students()->sync([]);
        } else {
            $liveClass->students()->sync($validated['student_ids'] ?? []);
            $this->syncTargets($liveClass, []);
        }
    }

    private function notifyAudience(LiveClass $liveClass): void
    {
        $studentIds = $liveClass->access_type === 'course'
            ? $liveClass->resolveAudienceIds()
            : $liveClass->students()->pluck('users.id');

        User::query()
            ->whereIn('id', $studentIds)
            ->each(fn (User $user) => $user->notify(new LiveClassScheduledNotification($liveClass)));
    }

    private function ownedCourses(int $tutorId): \Illuminate\Support\Collection
    {
        return Course::query()
            ->where('instructor_id', $tutorId)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    private function supervisedCohorts(int $userId): \Illuminate\Support\Collection
    {
        return Cohort::query()
            ->whereHas('programs', fn ($q) => $q->wherePivot('supervisor_id', $userId))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function supervisedPrograms(int $userId): \Illuminate\Support\Collection
    {
        return Program::query()
            ->whereHas('cohorts', fn ($q) => $q->wherePivot('supervisor_id', $userId))
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    private function reachableStudents(int $userId): \Illuminate\Support\Collection
    {
        $studentIds = $this->ownedCourses($userId)->flatMap(function (Course $course) {
            return $course->enrollments()
                ->whereIn('access_status', ['active', 'completed'])
                ->pluck('user_id');
        });

        $studentIds = $studentIds->merge(
            $this->supervisedCohorts($userId)->flatMap(fn (Cohort $cohort) => $cohort->studentIds())
        )->merge(
            $this->supervisedPrograms($userId)->flatMap(fn (Program $program) => $program->studentIds())
        )->unique();

        return User::query()->whereIn('id', $studentIds)->orderBy('name')->get(['id', 'name', 'email']);
    }
}
