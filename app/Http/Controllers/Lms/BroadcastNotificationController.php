<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\Lms\ManualLmsAnnouncementNotification;
use App\Services\LearningAudienceService;
use App\Support\EmailContentSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class BroadcastNotificationController extends Controller
{
    public function __construct(private readonly LearningAudienceService $audience) {}

    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return Inertia::render('Admin/Lms/Notifications/Index', [
            'mode' => 'admin',
            ...$this->audience->allGroups(),
        ]);
    }

    public function tutorIndex(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->canManageLearningOps(), 403);

        return Inertia::render('Tutor/Notifications/Index', [
            'mode' => 'tutor',
            ...$this->audience->managedGroupsFor($user),
        ]);
    }

    public function adminSend(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canAccessAdminPanel(), 403);

        return $this->send($request, true, null);
    }

    public function tutorSend(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->canManageLearningOps(), 403);

        return $this->send($request, false, (int) $user->id);
    }

    private function send(Request $request, bool $isAdmin, ?int $userId): RedirectResponse
    {
        $payload = $this->validatePayload($request, $isAdmin);
        $recipients = $this->resolveRecipients($payload, $isAdmin, $userId);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No recipients were found for the selected audience.');
        }

        $this->dispatchManualNotification($request, $recipients, $payload);

        return back()->with('success', sprintf('Notification sent to %d recipient(s).', $recipients->count()));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $allowAllStudents): array
    {
        $audiences = ['all_course_students', 'selected_students'];
        if ($allowAllStudents) {
            $audiences[] = 'all_students';
        }

        $validated = $request->validate([
            'audience' => ['required', 'string', 'in:'.implode(',', $audiences)],
            'attachable_type' => ['required_unless:audience,all_students', 'nullable', 'in:course,cohort,program'],
            'attachable_id' => ['required_unless:audience,all_students', 'nullable', 'integer'],
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
    private function resolveRecipients(array $payload, bool $isAdmin, ?int $userId): Collection
    {
        $audience = (string) $payload['audience'];

        // Admin-only: everyone.
        if ($audience === 'all_students' && $isAdmin) {
            return User::query()->where('role', 'user')->orderBy('name')->get();
        }

        // Otherwise the audience is an attachable (course / cohort / program).
        $attachable = $this->audience->resolveAttachable(
            (string) $payload['attachable_type'],
            (int) $payload['attachable_id'],
        );

        if (! $isAdmin) {
            abort_unless($this->audience->userCanManage($attachable, (int) $userId), 403);
        }

        $memberIds = $this->audience->studentIds($attachable);
        if ($memberIds->isEmpty()) {
            return collect();
        }

        // "Selected" narrows to chosen members; guard against ids outside the group.
        if ($audience === 'selected_students') {
            $chosen = collect($payload['student_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $memberIds->contains($id))
                ->values();

            if ($chosen->isEmpty()) {
                return collect();
            }

            $memberIds = $chosen;
        }

        return User::query()
            ->where('role', 'user')
            ->whereIn('id', $memberIds->all())
            ->orderBy('name')
            ->get();
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

        Notification::sendNow($recipients, $notification);
    }
}
