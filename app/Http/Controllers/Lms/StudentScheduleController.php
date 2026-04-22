<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\LiveClass;
use App\Models\LmsSchedule;
use App\Models\StudentScheduleEvent;
use App\Support\Lms\GoogleCalendarSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $enrolledCourseIds = Enrollment::query()
            ->where('user_id', $userId)
            ->where('access_status', '!=', 'revoked')
            ->pluck('course_id');

        $items = collect()
            ->merge($this->lmsSchedules($userId, $enrolledCourseIds))
            ->merge($this->assignmentItems($userId, $enrolledCourseIds))
            ->merge($this->liveClassItems($user, $enrolledCourseIds))
            ->merge($this->personalItems($userId))
            ->sortBy('starts_at')
            ->values();

        return Inertia::render('Lms/Schedules/Index', [
            'schedules' => $items->all(),
            'googleConnected' => (bool) $user->google_calendar_refresh_token,
            'googleEmail' => $user->google_calendar_email,
        ]);
    }

    public function storePersonal(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        StudentScheduleEvent::query()->create([
            'user_id' => (int) $request->user()->id,
            'title' => (string) $payload['title'],
            'description' => $payload['description'] ?? null,
            'location' => $payload['location'] ?? null,
            'starts_at' => (string) $payload['starts_at'],
            'ends_at' => $payload['ends_at'] ?? null,
        ]);

        return back()->with('success', 'Personal event added.');
    }

    public function updatePersonal(Request $request, StudentScheduleEvent $event): RedirectResponse
    {
        abort_unless((int) $event->user_id === (int) $request->user()->id, 403);

        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $event->update($payload);

        return back()->with('success', 'Personal event updated.');
    }

    public function destroyPersonal(Request $request, StudentScheduleEvent $event): RedirectResponse
    {
        abort_unless((int) $event->user_id === (int) $request->user()->id, 403);
        $event->delete();

        return back()->with('success', 'Personal event deleted.');
    }

    public function googleRedirect(Request $request, GoogleCalendarSyncService $service): RedirectResponse
    {
        return redirect()->away($service->authUrl($request->user()));
    }

    public function googleCallback(Request $request, GoogleCalendarSyncService $service): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string']]);
        $result = $service->exchangeCode((string) $validated['code']);

        $request->user()->forceFill([
            'google_calendar_refresh_token' => $result['refresh_token'],
            'google_calendar_email' => $result['email'],
        ])->save();

        return redirect()->route('lms.schedules.index')->with('success', 'Google Calendar connected.');
    }

    public function syncGoogle(Request $request, GoogleCalendarSyncService $service): RedirectResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $enrolledCourseIds = Enrollment::query()
            ->where('user_id', $userId)
            ->where('access_status', '!=', 'revoked')
            ->pluck('course_id');

        $items = collect()
            ->merge($this->lmsSchedules($userId, $enrolledCourseIds))
            ->merge($this->assignmentItems($userId, $enrolledCourseIds))
            ->merge($this->liveClassItems($user, $enrolledCourseIds))
            ->merge($this->personalItems($userId))
            ->values()
            ->all();

        $service->syncEvents($user, $items);

        return back()->with('success', 'Schedule synced to Google Calendar.');
    }

    private function lmsSchedules(int $userId, $enrolledCourseIds)
    {
        return LmsSchedule::query()
            ->where(function ($query) use ($userId, $enrolledCourseIds): void {
                $query->where('audience', 'all_students')
                    ->orWhere(function ($courseAudience) use ($enrolledCourseIds): void {
                        $courseAudience->where('audience', 'course_students')->whereIn('course_id', $enrolledCourseIds);
                    })
                    ->orWhere(function ($selectedAudience) use ($userId): void {
                        $selectedAudience->where('audience', 'selected_students')
                            ->whereHas('selectedStudents', fn ($selected) => $selected->where('users.id', $userId));
                    });
            })
            ->with(['course:id,title', 'creator:id,name'])
            ->get()
            ->map(fn (LmsSchedule $s) => [
                'id' => "schedule:{$s->id}",
                'uid' => "schedule-{$s->id}-{$userId}",
                'source_type' => 'schedule',
                'source_label' => 'Schedule',
                'title' => $s->title,
                'description' => $s->description,
                'location' => $s->location,
                'starts_at' => $s->starts_at?->toIso8601String(),
                'ends_at' => $s->ends_at?->toIso8601String(),
                'course' => ['title' => $s->course?->title],
                'can_edit' => false,
            ]);
    }

    private function assignmentItems(int $userId, $enrolledCourseIds)
    {
        return Assignment::query()
            ->where(function ($query) use ($userId, $enrolledCourseIds): void {
                $query->where(function ($courseAudience) use ($enrolledCourseIds): void {
                    $courseAudience->where('audience', 'course_students')
                        ->whereIn('course_id', $enrolledCourseIds);
                })->orWhere(function ($selectedAudience) use ($userId): void {
                    $selectedAudience->where('audience', 'selected_students')
                        ->whereHas('selectedStudents', fn ($selected) => $selected->where('users.id', $userId));
                });
            })
            ->with('course:id,title')
            ->whereNotNull('due_at')
            ->get()
            ->map(fn (Assignment $a) => [
                'id' => "assignment:{$a->id}",
                'uid' => "assignment-{$a->id}-{$userId}",
                'source_type' => 'assignment',
                'source_label' => 'Assignment due',
                'title' => "Assignment: {$a->title}",
                'description' => $a->instructions,
                'location' => null,
                'starts_at' => $a->due_at?->toIso8601String(),
                'ends_at' => $a->due_at?->toIso8601String(),
                'course' => ['title' => $a->course?->title],
                'can_edit' => false,
            ]);
    }

    private function liveClassItems($user, $enrolledCourseIds)
    {
        return LiveClass::query()
            ->with(['courses:id,title'])
            ->get()
            ->filter(fn (LiveClass $c) => $c->canUserJoin($user))
            ->map(fn (LiveClass $c) => [
                'id' => "live-class:{$c->id}",
                'uid' => "live-class-{$c->id}-{$user->id}",
                'source_type' => 'live_class',
                'source_label' => 'Live class',
                'title' => "Live: {$c->title}",
                'description' => $c->description,
                'location' => 'Online (Jitsi)',
                'starts_at' => $c->start_time?->toIso8601String(),
                'ends_at' => $c->endsAt()?->toIso8601String(),
                'course' => ['title' => $c->courses->first()?->title],
                'can_edit' => false,
            ]);
    }

    private function personalItems(int $userId)
    {
        return StudentScheduleEvent::query()
            ->where('user_id', $userId)
            ->get()
            ->map(fn (StudentScheduleEvent $e) => [
                'id' => "personal:{$e->id}",
                'uid' => "personal-{$e->id}-{$userId}",
                'source_type' => 'personal',
                'source_label' => 'Personal event',
                'title' => $e->title,
                'description' => $e->description,
                'location' => $e->location,
                'starts_at' => $e->starts_at?->toIso8601String(),
                'ends_at' => $e->ends_at?->toIso8601String(),
                'course' => ['title' => null],
                'can_edit' => true,
                'personal_event_id' => $e->id,
            ]);
    }
}
