<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\BatchEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class EmailController extends Controller
{
    public function index(): Response
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
        ];

        return Inertia::render('Admin/Emails/Index', [
            'recipientCounts' => $recipientCounts,
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipients' => 'required|in:all,with_applications,without_applications,accepted_applicants,pending_applicants,rejected_applicants,custom',
            'custom_emails' => 'nullable|array|required_if:recipients,custom',
            'custom_emails.*' => 'email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'action_text' => 'nullable|string|max:100',
            'action_url' => 'nullable|url|required_with:action_text',
        ]);

        $notification = new BatchEmailNotification(
            subject: $validated['subject'],
            message: $validated['message'],
            actionText: $validated['action_text'],
            actionUrl: $validated['action_url']
        );

        $recipientCount = 0;

        // Handle custom emails - can be non-registered users
        if ($validated['recipients'] === 'custom') {
            $customEmails = $validated['custom_emails'] ?? [];

            foreach ($customEmails as $email) {
                // Check if user exists in database
                $user = User::where('email', $email)->first();

                if ($user) {
                    $user->notify($notification);
                } else {
                    // Send to non-registered email using AnonymousNotifiable
                    Notification::route('mail', $email)->notify($notification);
                }
                $recipientCount++;
            }
        } else {
            $users = $this->getRecipients($validated['recipients'], []);

            if ($users->isEmpty()) {
                return back()->with('error', 'No recipients found for the selected filter.');
            }

            Notification::send($users, $notification);
            $recipientCount = $users->count();
        }

        if ($recipientCount === 0) {
            return back()->with('error', 'No recipients found.');
        }

        return back()->with('success', "Email queued for {$recipientCount} recipient(s).");
    }

    public function preview(Request $request): Response
    {
        $validated = $request->validate([
            'recipients' => 'required|in:all,with_applications,without_applications,accepted_applicants,pending_applicants,rejected_applicants,custom',
            'custom_emails' => 'nullable|array',
        ]);

        $users = $this->getRecipients($validated['recipients'], $validated['custom_emails'] ?? []);

        return Inertia::render('Admin/Emails/Preview', [
            'recipientCount' => $users->count(),
            'sampleRecipients' => $users->take(10)->map(fn ($u) => ['name' => $u->name, 'email' => $u->email]),
        ]);
    }

    private function getRecipients(string $filter, array $customEmails): \Illuminate\Database\Eloquent\Collection
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
            'custom' => User::whereIn('email', $customEmails)->get(),
            default => collect(),
        };
    }
}
