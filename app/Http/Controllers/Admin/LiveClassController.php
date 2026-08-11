<?php

namespace App\Http\Controllers\Admin;

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

    public function index(): Response
    {
        $classes = LiveClass::query()
            ->with(['tutor:id,name', 'courses:id,title'])
            ->latest('start_time')
            ->get();

        return Inertia::render('Admin/Lms/LiveClasses/Index', [
            'classes' => $classes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Lms/LiveClasses/Create', [
            'tutors' => User::query()->whereIn('role', [User::ROLE_TUTOR, User::ROLE_SUPERVISOR, User::ROLE_PROGRAM_COORDINATOR])->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'cohorts' => Cohort::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('title')->get(['id', 'title']),
            'students' => $this->studentsWithInternship(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tutor_id' => ['required', 'exists:users,id'],
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
            'tutor_id' => $validated['tutor_id'],
            'created_by' => (int) $request->user()->id,
            'start_time' => $validated['start_time'],
            'duration' => $validated['duration'],
            'room_name' => 'class_'.Str::random(10),
            'access_type' => $validated['access_type'],
        ]);

        $this->ensureMeetingLink($liveClass, $validated['meeting_url'] ?? null);

        if ($liveClass->access_type === 'course') {
            $this->syncTargets($liveClass, $validated['targets'] ?? []);
            $liveClass->students()->sync([]);
        } else {
            $liveClass->students()->sync($validated['student_ids'] ?? []);
            $this->syncTargets($liveClass, []);
        }

        $this->notifyAudience($liveClass);

        return redirect()->route('admin.lms.live-classes.index')->with('success', 'Live class scheduled.');
    }

    public function show(LiveClass $liveClass): Response
    {
        $liveClass->load([
            'tutor:id,name,email',
            'courses:id,title',
            'students:id,name,email',
            'recordings',
            'attendance.student:id,name,email',
        ]);

        return Inertia::render('Admin/Lms/LiveClasses/Show', [
            'liveClass' => $liveClass,
            'targets' => $this->targetRowsForDisplay($liveClass),
        ]);
    }

    public function edit(LiveClass $liveClass): Response
    {
        $liveClass->load(['courses:id,title', 'students:id,name,email']);

        return Inertia::render('Admin/Lms/LiveClasses/Edit', [
            'liveClass' => $liveClass,
            'targets' => $this->targetRowsForDisplay($liveClass),
            'tutors' => User::query()->whereIn('role', [User::ROLE_TUTOR, User::ROLE_SUPERVISOR, User::ROLE_PROGRAM_COORDINATOR])->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'cohorts' => Cohort::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('title')->get(['id', 'title']),
            'students' => $this->studentsWithInternship(),
        ]);
    }

    public function update(Request $request, LiveClass $liveClass): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tutor_id' => ['required', 'exists:users,id'],
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
            'tutor_id' => $validated['tutor_id'],
            'start_time' => $validated['start_time'],
            'duration' => $validated['duration'],
            'access_type' => $validated['access_type'],
        ]);

        $this->ensureMeetingLink($liveClass, $validated['meeting_url'] ?? null);

        if ($liveClass->access_type === 'course') {
            $this->syncTargets($liveClass, $validated['targets'] ?? []);
            $liveClass->students()->sync([]);
        } else {
            $liveClass->students()->sync($validated['student_ids'] ?? []);
            $this->syncTargets($liveClass, []);
        }

        return back()->with('success', 'Live class updated.');
    }

    public function destroy(LiveClass $liveClass): RedirectResponse
    {
        app(GoogleMeetService::class)->deleteMeeting($liveClass->meeting_event_id);
        $liveClass->delete();

        return back()->with('success', 'Live class deleted.');
    }

    public function addRecording(Request $request, LiveClass $liveClass): RedirectResponse
    {
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

    /**
     * All students, each tagged with their internship's cohort/program (if
     * any) so the "custom students" picker can be filtered by cohort/program.
     */
    private function studentsWithInternship(): \Illuminate\Support\Collection
    {
        return User::query()
            ->where('role', User::ROLE_USER)
            ->with(['internships' => fn ($q) => $q->latest('id')])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'cohort_id' => $u->internships->first()?->cohort_id,
                'program_id' => $u->internships->first()?->program_id,
            ]);
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
}
