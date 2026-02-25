<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Notifications\ApplicationAcceptanceNotification;
use App\Notifications\BatchEmailNotification;
use App\Notifications\InterviewInvitationNotification;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Application::with(['program', 'user'])
            ->withSum(['payments as successful_payments_sum_amount' => function ($paymentQuery) {
                $paymentQuery->where('status', 'successful');
            }], 'base_amount')
            ->withCount(['payments as successful_payments_count' => function ($paymentQuery) {
                $paymentQuery->where('status', 'successful');
            }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->string('country')->toString());
        }

        if ($request->filled('interview_status')) {
            $query->where('interview_status', $request->string('interview_status')->toString());
        }

        if ($request->filled('submitted_from')) {
            $query->whereDate('created_at', '>=', $request->date('submitted_from'));
        }

        if ($request->filled('submitted_to')) {
            $query->whereDate('created_at', '<=', $request->date('submitted_to'));
        }

        $applications = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function (Application $application) {
                $application->setAttribute('payment_summary', $this->buildPaymentSummary($application));

                return $application;
            });

        $stats = [
            'total' => Application::count(),
            'pending' => Application::where('status', 'pending')->count(),
            'accepted' => Application::where('status', 'accepted')->count(),
            'rejected' => Application::where('status', 'rejected')->count(),
        ];

        $interviews = Interview::where('is_active', true)
            ->select('id', 'title', 'program_id')
            ->withCount('questions')
            ->get();

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search', 'status', 'program_id', 'country', 'interview_status', 'submitted_from', 'submitted_to']),
            'stats' => $stats,
            'programs' => \App\Models\Program::select('id', 'title')->get(),
            'interviews' => $interviews,
            'countries' => Application::query()
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->distinct()
                ->orderBy('country')
                ->pluck('country')
                ->values(),
        ]);
    }

    public function show(Application $application): Response
    {
        $application->load([
            'program',
            'user',
            'interview',
            'payments' => function ($query) {
                $query->where('status', 'successful')->latest('paid_at');
            },
        ]);

        $availableInterviews = Interview::where('is_active', true)
            ->where('program_id', $application->program_id)
            ->select('id', 'title', 'description', 'passing_score', 'time_limit_minutes')
            ->withCount('questions')
            ->get();

        $interviewResponse = null;
        if ($application->interview_id && $application->user_id) {
            $interviewResponse = \App\Models\InterviewResponse::where('interview_id', $application->interview_id)
                ->where('user_id', $application->user_id)
                ->with('answers.question')
                ->first();
        }

        return Inertia::render('Admin/Applications/Show', [
            'application' => $application,
            'availableInterviews' => $availableInterviews,
            'interviewResponse' => $interviewResponse,
            'paymentSummary' => $this->buildPaymentSummary($application),
            'successfulPayments' => $application->payments,
        ]);
    }

    public function edit(Application $application): Response
    {
        $application->load(['program', 'user']);

        return Inertia::render('Admin/Applications/Edit', [
            'application' => $application,
            'programs' => \App\Models\Program::query()
                ->select('id', 'title')
                ->orderBy('title')
                ->get(),
        ]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $wasAccepted = $application->status === 'accepted';

        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:pending,accepted,rejected'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'program_id' => (int) $validated['program_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'country' => $validated['country'] ?? null,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ];

        if ($application->program_id !== (int) $validated['program_id']) {
            $payload['interview_id'] = null;
            $payload['interview_status'] = null;
            $payload['interview_scheduled_at'] = null;
        }

        if ($validated['status'] === 'pending') {
            $payload['reviewed_at'] = null;
        } else {
            $payload['reviewed_at'] = $application->reviewed_at ?? now();
        }

        $application->update($payload);

        if (! $wasAccepted && $application->status === 'accepted') {
            $this->sendAcceptanceNotification($application);
        }

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Application updated successfully.');
    }

    public function sendPaymentReminder(Application $application): RedirectResponse
    {
        $application->load(['program', 'user'])
            ->loadSum(['payments as successful_payments_sum_amount' => function ($query) {
                $query->where('status', 'successful');
            }], 'base_amount')
            ->loadCount(['payments as successful_payments_count' => function ($query) {
                $query->where('status', 'successful');
            }]);

        $summary = $this->buildPaymentSummary($application);

        if (! $summary['can_send_reminder']) {
            return back()->with('error', 'This applicant does not currently need a payment reminder.');
        }

        $application->user?->notify(new PaymentReminderNotification($application, $summary));

        return back()->with('success', 'Payment reminder email sent successfully.');
    }

    public function bulkPaymentReminder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:applications,id',
        ]);

        $applications = Application::query()
            ->whereIn('id', $validated['ids'])
            ->with(['program', 'user'])
            ->withSum(['payments as successful_payments_sum_amount' => function ($query) {
                $query->where('status', 'successful');
            }], 'base_amount')
            ->withCount(['payments as successful_payments_count' => function ($query) {
                $query->where('status', 'successful');
            }])
            ->get();

        [$sent, $skipped] = $this->sendBulkReminders($applications);

        if ($sent === 0) {
            return back()->with('error', 'No eligible applications were found for payment reminders.');
        }

        $message = "Payment reminder sent to {$sent} applicant(s).";

        if ($skipped > 0) {
            $message .= " {$skipped} applicant(s) skipped (already paid, free program, or not accepted).";
        }

        return back()->with('success', $message);
    }

    public function accept(Application $application): RedirectResponse
    {
        $wasAccepted = $application->status === 'accepted';

        $application->update([
            'status' => 'accepted',
            'reviewed_at' => now(),
        ]);

        if (! $wasAccepted) {
            $this->sendAcceptanceNotification($application);
        }

        return back()->with('success', 'Application accepted successfully.');
    }

    public function reject(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $application->update([
            'status' => 'rejected',
            'notes' => $validated['notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        // Notify the applicant
        if ($application->user) {
            $application->user->notify(new BatchEmailNotification(
                subject: 'Update on Your Application',
                messageHtml: "<p>Thank you for your interest in {$application->program->title}. After careful review, we regret to inform you that we are unable to proceed with your application at this time. Please feel free to apply for other programs that match your profile.</p>",
                actionText: 'Explore Programs',
                actionUrl: url('/programs')
            ));
        }

        return back()->with('success', 'Application rejected.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:applications,id',
            'action' => 'required|in:accept,reject,delete',
        ]);

        $applications = Application::whereIn('id', $validated['ids'])->with(['user', 'program'])->get();

        foreach ($applications as $application) {
            if ($validated['action'] === 'accept') {
                $wasAccepted = $application->status === 'accepted';

                $application->update(['status' => 'accepted', 'reviewed_at' => now()]);
                if (! $wasAccepted) {
                    $this->sendAcceptanceNotification($application);
                }
            } elseif ($validated['action'] === 'reject') {
                $application->update(['status' => 'rejected', 'reviewed_at' => now()]);
                if ($application->user) {
                    $application->user->notify(new BatchEmailNotification(
                        subject: 'Update on Your Application',
                        messageHtml: "<p>Thank you for your interest in {$application->program->title}. After careful review, we regret to inform you that we are unable to proceed with your application at this time. Please feel free to apply for other programs that match your profile.</p>",
                        actionText: 'Explore Programs',
                        actionUrl: url('/programs')
                    ));
                }
            } elseif ($validated['action'] === 'delete') {
                $application->delete();
            }
        }

        return back()->with('success', 'Bulk action completed successfully.');
    }

    public function scheduleInterview(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'interview_id' => 'required|exists:interviews,id',
        ]);

        $interview = Interview::where('id', $validated['interview_id'])
            ->where('program_id', $application->program_id)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $application->user) {
            return back()->with('error', 'Cannot schedule an interview for an application without a linked user account.');
        }

        $application->update([
            'interview_id' => $interview->id,
            'interview_scheduled_at' => now(),
            'interview_status' => 'scheduled',
        ]);

        $application->user->notify(new InterviewInvitationNotification($application, $interview));

        return back()->with('success', "Interview \"{$interview->title}\" scheduled and invitation sent to {$application->first_name} {$application->last_name}.");
    }

    public function destroy(Application $application): RedirectResponse
    {
        $application->delete();

        return back()->with('success', 'Application deleted successfully.');
    }

    public function bulkScheduleInterview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:applications,id',
            'interview_id' => 'required|exists:interviews,id',
        ]);

        $interview = Interview::where('id', $validated['interview_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $applications = Application::whereIn('id', $validated['ids'])
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        $scheduledCount = 0;
        $skippedCount = 0;

        foreach ($applications as $application) {
            // Skip if already has this interview scheduled
            if ($application->interview_id === $interview->id) {
                $skippedCount++;

                continue;
            }

            $application->update([
                'interview_id' => $interview->id,
                'interview_scheduled_at' => now(),
                'interview_status' => 'scheduled',
            ]);

            if ($application->user) {
                $application->user->notify(new InterviewInvitationNotification($application, $interview));
            }

            $scheduledCount++;
        }

        $message = "Interview \"{$interview->title}\" scheduled for {$scheduledCount} applicant(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} already had this interview scheduled.";
        }

        return back()->with('success', $message);
    }

    /**
     * @return array{program_price: float, paid_amount: float, remaining_amount: float, max_installments: int, completed_installments: int, status: string, can_send_reminder: bool}
     */
    private function buildPaymentSummary(Application $application): array
    {
        $programPrice = (float) ($application->program?->price ?? 0);
        $maxInstallments = max(1, (int) ($application->program?->max_installments ?? 1));
        $paidAmount = (float) ($application->successful_payments_sum_amount ?? 0);
        $completedInstallments = (int) ($application->successful_payments_count ?? 0);
        $remainingAmount = max(0, round($programPrice - $paidAmount, 2));

        $status = $programPrice <= 0
            ? 'not-required'
            : ($remainingAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially-paid' : 'unpaid'));

        return [
            'program_price' => $programPrice,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'max_installments' => $maxInstallments,
            'completed_installments' => $completedInstallments,
            'status' => $status,
            'can_send_reminder' => $application->status === 'accepted'
                && $programPrice > 0
                && $remainingAmount > 0
                && $application->user !== null,
        ];
    }

    /**
     * @param  Collection<int, Application>  $applications
     * @return array{int, int}
     */
    private function sendBulkReminders(Collection $applications): array
    {
        $sent = 0;
        $skipped = 0;

        foreach ($applications as $application) {
            $summary = $this->buildPaymentSummary($application);

            if (! $summary['can_send_reminder']) {
                $skipped++;

                continue;
            }

            $application->user?->notify(new PaymentReminderNotification($application, $summary));
            $sent++;
        }

        return [$sent, $skipped];
    }

    private function sendAcceptanceNotification(Application $application): void
    {
        $application->loadMissing(['program', 'user']);

        if ($application->user) {
            $application->user->notify(new ApplicationAcceptanceNotification($application));

            return;
        }

        if (! empty($application->email)) {
            (new AnonymousNotifiable)
                ->route('mail', $application->email)
                ->notify(new ApplicationAcceptanceNotification($application));
        }
    }
}
