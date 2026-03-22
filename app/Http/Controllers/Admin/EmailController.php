<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use App\Models\User;
use App\Notifications\BatchEmailNotification;
use App\Support\EmailContentSanitizer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmailController extends Controller
{
    public function index(Request $request): Response
    {
        $recipientCounts = [
            'all' => User::where('role', 'user')->count(),
            'with_applications' => User::where('role', 'user')->has('applications')->count(),
            'without_applications' => User::where('role', 'user')->doesntHave('applications')->count(),
            'accepted_applicants' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('status', 'accepted'))
                ->count(),
            'pending_applicants' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('status', 'pending'))
                ->count(),
            'rejected_applicants' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('status', 'rejected'))
                ->count(),
            'interview_scheduled' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('interview_status', 'scheduled'))
                ->count(),
            'interview_completed' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('interview_status', 'completed'))
                ->count(),
            'interview_passed' => User::where('role', 'user')
                ->whereHas('interviewResponses', fn ($q) => $q->where('passed', true))
                ->count(),
            'interview_not_passed' => User::where('role', 'user')
                ->whereHas('interviewResponses', fn ($q) => $q->where('passed', false)->where('status', 'completed'))
                ->count(),
        ];

        $adminId = $request->user()?->id;
        $emailHistory = EmailCampaign::query()
            ->where('admin_id', $adminId)
            ->with('admin:id,name')
            ->latest()
            ->take(15)
            ->get()
            ->map(function (EmailCampaign $campaign) {
                $previewRecipients = $campaign->recipients()
                    ->select('email')
                    ->limit(10)
                    ->pluck('email');

                return [
                    'id' => $campaign->id,
                    'subject' => $campaign->subject,
                    'audience' => $campaign->audience,
                    'audience_label' => $this->audienceLabel($campaign->audience),
                    'message_html' => $campaign->message_html,
                    'action_text' => $campaign->action_text,
                    'action_url' => $campaign->action_url,
                    'recipient_count' => $campaign->recipient_count,
                    'sent_by' => $campaign->admin?->name ?? 'Unknown',
                    'sent_at' => $campaign->created_at?->toIso8601String(),
                    'preview_recipients' => $previewRecipients->values(),
                ];
            });

        return Inertia::render('Admin/Emails/Index', [
            'recipientCounts' => $recipientCounts,
            'emailHistory' => $emailHistory,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipients' => 'required|in:all,with_applications,without_applications,accepted_applicants,pending_applicants,rejected_applicants,interview_scheduled,interview_completed,interview_passed,interview_not_passed,custom',
            'custom_emails' => 'nullable|array|required_if:recipients,custom',
            'custom_emails.*' => 'email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:20000',
            'action_text' => 'nullable|string|max:100|required_with:action_url',
            'action_url' => 'nullable|url|required_with:action_text',
        ]);

        $messageHtml = EmailContentSanitizer::sanitize($validated['message']);

        if ($messageHtml === '') {
            throw ValidationException::withMessages([
                'message' => 'The email body is empty after formatting cleanup. Please add message content.',
            ]);
        }

        $recipientEntries = $this->resolveRecipients($validated['recipients'], $validated['custom_emails'] ?? []);
        if ($recipientEntries->isEmpty()) {
            throw ValidationException::withMessages([
                'recipients' => 'No recipients found for the selected audience.',
            ]);
        }

        $notification = new BatchEmailNotification(
            subject: $validated['subject'],
            messageHtml: $messageHtml,
            actionText: $validated['action_text'] ?? null,
            actionUrl: $validated['action_url'] ?? null
        );

        $recipientCount = $this->dispatchNotifications($recipientEntries, $notification);
        $this->storeCampaign($request, $validated, $messageHtml, $recipientEntries, $recipientCount);

        return back()->with('success', "Email queued for {$recipientCount} recipient(s).");
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('media')->store('email-media', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'name' => $validated['media']->getClientOriginalName(),
        ]);
    }

    public function preview(Request $request): Response
    {
        $validated = $request->validate([
            'recipients' => 'required|in:all,with_applications,without_applications,accepted_applicants,pending_applicants,rejected_applicants,interview_scheduled,interview_completed,interview_passed,interview_not_passed,custom',
            'custom_emails' => 'nullable|array',
        ]);

        $recipients = $this->resolveRecipients($validated['recipients'], $validated['custom_emails'] ?? []);

        return Inertia::render('Admin/Emails/Preview', [
            'recipientCount' => $recipients->count(),
            'sampleRecipients' => $recipients->take(10)->map(fn (array $recipient) => [
                'name' => $recipient['user']?->name ?? null,
                'email' => $recipient['email'],
            ]),
        ]);
    }

    private function getRecipients(string $filter): EloquentCollection
    {
        return match ($filter) {
            'all' => User::where('role', 'user')->get(),
            'with_applications' => User::where('role', 'user')->has('applications')->get(),
            'without_applications' => User::where('role', 'user')->doesntHave('applications')->get(),
            'accepted_applicants' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('status', 'accepted'))
                ->get(),
            'pending_applicants' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('status', 'pending'))
                ->get(),
            'rejected_applicants' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('status', 'rejected'))
                ->get(),
            'interview_scheduled' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('interview_status', 'scheduled'))
                ->get(),
            'interview_completed' => User::where('role', 'user')
                ->whereHas('applications', fn ($q) => $q->where('interview_status', 'completed'))
                ->get(),
            'interview_passed' => User::where('role', 'user')
                ->whereHas('interviewResponses', fn ($q) => $q->where('passed', true))
                ->get(),
            'interview_not_passed' => User::where('role', 'user')
                ->whereHas('interviewResponses', fn ($q) => $q->where('passed', false)->where('status', 'completed'))
                ->get(),
            default => new EloquentCollection,
        };
    }

    private function resolveRecipients(string $filter, array $customEmails): Collection
    {
        if ($filter === 'custom') {
            $emails = collect($customEmails)
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->filter()
                ->unique()
                ->values();

            if ($emails->isEmpty()) {
                return collect();
            }

            $usersByEmail = User::query()
                ->whereIn('email', $emails->all())
                ->get()
                ->keyBy(fn (User $user) => strtolower($user->email));

            return $emails->map(fn (string $email) => [
                'email' => $email,
                'user' => $usersByEmail->get($email),
            ]);
        }

        return $this->getRecipients($filter)->map(fn (User $user) => [
            'email' => $user->email,
            'user' => $user,
        ]);
    }

    private function dispatchNotifications(Collection $recipientEntries, BatchEmailNotification $notification): int
    {
        $recipientEntries->each(function (array $recipient) use ($notification) {
            /** @var User|null $user */
            $user = $recipient['user'];
            $email = $recipient['email'];

            if ($user) {
                $user->notify($notification);

                return;
            }

            Notification::route('mail', $email)->notify($notification);
        });

        return $recipientEntries->count();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function storeCampaign(
        Request $request,
        array $validated,
        string $messageHtml,
        Collection $recipientEntries,
        int $recipientCount
    ): void {
        $campaign = EmailCampaign::create([
            'admin_id' => $request->user()?->id,
            'audience' => $validated['recipients'],
            'subject' => $validated['subject'],
            'message_html' => $messageHtml,
            'action_text' => $validated['action_text'] ?? null,
            'action_url' => $validated['action_url'] ?? null,
            'recipient_count' => $recipientCount,
        ]);

        $timestamp = now();
        $rows = $recipientEntries->map(function (array $recipient) use ($campaign, $timestamp) {
            /** @var User|null $user */
            $user = $recipient['user'];

            return [
                'email_campaign_id' => $campaign->id,
                'user_id' => $user?->id,
                'email' => $recipient['email'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            EmailCampaignRecipient::insert($chunk);
        }
    }

    private function audienceLabel(string $audience): string
    {
        return match ($audience) {
            'all' => 'All Users',
            'with_applications' => 'Users with Applications',
            'without_applications' => 'Users without Applications',
            'accepted_applicants' => 'Accepted Applicants',
            'pending_applicants' => 'Pending Applicants',
            'rejected_applicants' => 'Rejected Applicants',
            'interview_scheduled' => 'Interview Scheduled',
            'interview_completed' => 'Interview Completed',
            'interview_passed' => 'Interview Passed',
            'interview_not_passed' => 'Interview Not Passed',
            'custom' => 'Custom List',
            default => ucfirst(str_replace('_', ' ', $audience)),
        };
    }
}
