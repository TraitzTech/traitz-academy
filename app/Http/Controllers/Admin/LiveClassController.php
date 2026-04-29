<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\UploadLiveClassRecordingToYouTube;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\LiveClassRecording;
use App\Models\User;
use App\Notifications\Lms\LiveClassScheduledNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LiveClassController extends Controller
{
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
            'tutors' => User::query()->where('role', User::ROLE_TUTOR)->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'students' => User::query()->where('role', User::ROLE_USER)->orderBy('name')->get(['id', 'name', 'email']),
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
            'course_ids' => ['array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
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

        if ($liveClass->access_type === 'course') {
            $liveClass->courses()->sync($validated['course_ids'] ?? []);
        } else {
            $liveClass->students()->sync($validated['student_ids'] ?? []);
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
        ]);
    }

    public function edit(LiveClass $liveClass): Response
    {
        $liveClass->load(['courses:id,title', 'students:id,name,email']);

        return Inertia::render('Admin/Lms/LiveClasses/Edit', [
            'liveClass' => $liveClass,
            'tutors' => User::query()->where('role', User::ROLE_TUTOR)->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->orderBy('title')->get(['id', 'title']),
            'students' => User::query()->where('role', User::ROLE_USER)->orderBy('name')->get(['id', 'name', 'email']),
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
            'course_ids' => ['array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
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

        if ($liveClass->access_type === 'course') {
            $liveClass->courses()->sync($validated['course_ids'] ?? []);
            $liveClass->students()->sync([]);
        } else {
            $liveClass->students()->sync($validated['student_ids'] ?? []);
            $liveClass->courses()->sync([]);
        }

        return back()->with('success', 'Live class updated.');
    }

    public function destroy(LiveClass $liveClass): RedirectResponse
    {
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

    private function notifyAudience(LiveClass $liveClass): void
    {
        if ($liveClass->access_type === 'course') {
            $studentIds = $liveClass->courses()
                ->with('enrollments:user_id,course_id,access_status')
                ->get()
                ->flatMap(fn (Course $course) => $course->enrollments
                    ->whereIn('access_status', ['active', 'completed'])
                    ->pluck('user_id'))
                ->unique()
                ->values();
        } else {
            $studentIds = $liveClass->students()->pluck('users.id');
        }

        User::query()
            ->whereIn('id', $studentIds)
            ->each(fn (User $user) => $user->notify(new LiveClassScheduledNotification($liveClass)));
    }
}
