<?php

namespace App\Http\Controllers\Tutor;

use App\Concerns\SyncsLiveClassTargets;
use App\Http\Controllers\Concerns\GeneratesLiveClassMeeting;
use App\Http\Controllers\Controller;
use App\Jobs\UploadLiveClassRecordingToYouTube;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\LiveClassRecording;
use App\Models\Program;
use App\Models\User;
use App\Notifications\Lms\LiveClassScheduledNotification;
use App\Services\LearningAudienceService;
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

    public function __construct(private readonly LearningAudienceService $audience) {}

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
            // Supervisors target their program, not whole cohorts.
            'cohorts' => collect(),
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
            'cohorts' => collect(),
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
        $isAdmin = (bool) $request->user()->canAccessAdminPanel();

        if ($liveClass->access_type === 'custom') {
            $studentIds = collect($validated['student_ids'] ?? [])->map(fn ($id) => (int) $id);

            // A supervisor may only hand-pick their own reachable interns.
            if (! $isAdmin) {
                $reachable = $this->reachableStudentIds($userId);
                $studentIds = $studentIds->filter(fn ($id) => $reachable->contains($id));
            }

            $liveClass->students()->sync($studentIds->unique()->values()->all());
            $this->syncTargets($liveClass, []);

            return;
        }

        // access_type === 'course': keep only targets the user owns/supervises —
        // same authorization the other learning-ops tools use.
        $targets = collect($validated['targets'] ?? [])
            ->filter(function ($target) use ($userId) {
                $modelClass = LearningAudienceService::TYPES[$target['type'] ?? ''] ?? null;
                $model = $modelClass ? $modelClass::find((int) $target['id']) : null;

                return $model !== null && $this->audience->userCanManage($model, $userId);
            })
            ->values();

        // A supervisor's program target resolves program-wide — across every
        // cohort/batch — in resolveAudienceIds()/visibility, which would reach
        // interns in cohorts owned by other supervisors. Pin the audience to
        // the supervisor's exact interns by demoting to an explicit custom
        // roster. Admins keep the broad program target.
        if (! $isAdmin && $targets->contains(fn ($t) => $t['type'] === 'program')) {
            $studentIds = $targets->flatMap(fn ($t) => match ($t['type']) {
                'program' => $this->audience->supervisedProgramStudentIds($userId, Program::findOrFail((int) $t['id'])),
                'course' => $this->audience->studentIds(Course::findOrFail((int) $t['id'])),
                default => collect(),
            })->unique()->values();

            $liveClass->update(['access_type' => 'custom']);
            $liveClass->students()->sync($studentIds->all());
            $this->syncTargets($liveClass, []);

            return;
        }

        $this->syncTargets($liveClass, $targets->all());
        $liveClass->students()->sync([]);
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

    private function supervisedPrograms(int $userId): \Illuminate\Support\Collection
    {
        return $this->audience->programsSupervisedBy($userId)->orderBy('title')->get(['id', 'title']);
    }

    /**
     * Student/intern IDs this user may put in a custom-audience class: their
     * course rosters plus the interns in the cohorts where they supervise each
     * program (never program-wide across cohorts owned by others).
     */
    private function reachableStudentIds(int $userId): \Illuminate\Support\Collection
    {
        $courseStudentIds = $this->ownedCourses($userId)->flatMap(function (Course $course) {
            return $course->enrollments()
                ->whereIn('access_status', ['active', 'completed'])
                ->pluck('user_id');
        });

        $programStudentIds = $this->supervisedPrograms($userId)
            ->flatMap(fn (Program $program) => $this->audience->supervisedProgramStudentIds($userId, $program));

        return $courseStudentIds->merge($programStudentIds)->unique()->values();
    }

    private function reachableStudents(int $userId): \Illuminate\Support\Collection
    {
        return User::query()
            ->whereIn('id', $this->reachableStudentIds($userId))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
