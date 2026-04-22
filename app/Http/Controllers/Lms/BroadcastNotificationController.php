<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\Lms\ManualLmsAnnouncementNotification;
use App\Support\EmailContentSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastNotificationController extends Controller
{
    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return Inertia::render('Admin/Lms/Notifications/Index', [
            'mode' => 'admin',
            'courses' => $this->coursesForAdmin(),
        ]);
    }

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->isTutor(), 403);

        return Inertia::render('Tutor/Notifications/Index', [
            'mode' => 'tutor',
            'courses' => $this->coursesForTutor((int) $user->id),
        ]);
    }

    public function adminSend(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        $payload = $this->validatePayload($request, true);
        $recipients = $this->resolveRecipients($payload, true, null);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No students were found for the selected audience.');
        }

        $this->dispatchManualNotification($request, $recipients, $payload);

        return back()->with('success', sprintf('Notification sent to %d learner(s).', $recipients->count()));
    }

    public function tutorSend(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isTutor(), 403);

        $payload = $this->validatePayload($request, false);
        $recipients = $this->resolveRecipients($payload, false, (int) $user->id);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No students were found for the selected audience.');
        }

        $this->dispatchManualNotification($request, $recipients, $payload);

        return back()->with('success', sprintf('Notification sent to %d learner(s).', $recipients->count()));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $allowAllStudents): array
    {
        $audiences = ['course_all', 'course_selected'];
        if ($allowAllStudents) {
            $audiences[] = 'all_students';
        }

        $validated = $request->validate([
            'audience' => ['required', 'string', 'in:'.implode(',', $audiences)],
            'course_id' => ['nullable', 'integer'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:20000'],
            'action_text' => ['nullable', 'string', 'max:100', 'required_with:action_url'],
            'action_url' => ['nullable', 'url', 'required_with:action_text'],
        ]);

        $messageHtml = EmailContentSanitizer::sanitize((string) $validated['message']);
        if ($messageHtml === '') {
            abort(422, 'The notification body is empty after formatting cleanup.');
        }

        $validated['message_html'] = $messageHtml;

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, User>
     */
    private function resolveRecipients(array $payload, bool $isAdmin, ?int $tutorId): Collection
    {
        $audience = (string) $payload['audience'];

        if ($audience === 'all_students' && $isAdmin) {
            return User::query()
                ->where('role', 'user')
                ->orderBy('name')
                ->get();
        }

        $courseId = (int) ($payload['course_id'] ?? 0);
        if ($courseId <= 0) {
            return collect();
        }

        $course = Course::query()->find($courseId);
        if (! $course) {
            return collect();
        }

        if (! $isAdmin && $tutorId !== null && (int) $course->instructor_id !== $tutorId) {
            return collect();
        }

        $enrolledUserIds = Enrollment::query()
            ->where('course_id', $courseId)
            ->where('access_status', '!=', 'revoked')
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($enrolledUserIds->isEmpty()) {
            return collect();
        }

        $query = User::query()
            ->where('role', 'user')
            ->whereIn('id', $enrolledUserIds->all());

        if ($audience === 'course_selected') {
            $ids = collect($payload['student_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->values();
            if ($ids->isEmpty()) {
                return collect();
            }
            $query->whereIn('id', $ids->all());
        }

        return $query->orderBy('name')->get();
    }

    /**
     * @param  Collection<int, User>  $recipients
     * @param  array<string, mixed>  $payload
     */
    private function dispatchManualNotification(Request $request, Collection $recipients, array $payload): void
    {
        $notification = new ManualLmsAnnouncementNotification(
            subject: (string) $payload['subject'],
            messageHtml: (string) $payload['message_html'],
            actionText: isset($payload['action_text']) ? (string) $payload['action_text'] : null,
            actionUrl: isset($payload['action_url']) ? (string) $payload['action_url'] : null,
            senderName: $request->user()?->name
        );

        Notification::send($recipients, $notification);
    }

    /**
     * @return array<int, array{id:int,title:string,students:array<int, array{id:int,name:string,email:string}>,student_count:int}>
     */
    private function coursesForAdmin(): array
    {
        $courses = Course::query()
            ->select('id', 'title', 'instructor_id')
            ->with(['enrollments:id,course_id,user_id,access_status', 'instructor:id,name', 'enrollments.user:id,name,email,role'])
            ->orderBy('title')
            ->get();

        return $courses->map(function (Course $course): array {
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
                'students' => $students,
                'student_count' => count($students),
            ];
        })->all();
    }

    /**
     * @return array<int, array{id:int,title:string,students:array<int, array{id:int,name:string,email:string}>,student_count:int}>
     */
    private function coursesForTutor(int $tutorId): array
    {
        $courses = Course::query()
            ->where('instructor_id', $tutorId)
            ->select('id', 'title')
            ->with(['enrollments:id,course_id,user_id,access_status', 'enrollments.user:id,name,email,role'])
            ->orderBy('title')
            ->get();

        return $courses->map(function (Course $course): array {
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
                'students' => $students,
                'student_count' => count($students),
            ];
        })->all();
    }
}
