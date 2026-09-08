<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Cards shown inline on the dashboard before linking out to the full
     * "My Programs" list — keeps the page short for interns accepted into
     * several programs.
     */
    private const INLINE_PAYMENT_LIMIT = 3;

    public function index()
    {
        $user = auth()->user();

        // Supervisors are staff, not learners — send them to their own home
        // instead of the applicant dashboard (which is empty for them).
        if (! $user->isTutor() && ! $user->canAccessAdminPanel()
            && ($user->isSupervisor() || $user->supervisesInterns())) {
            return redirect()->route('supervisor.dashboard');
        }

        // Same for a TAC leader (track mentor, school lead, etc.) who holds
        // no other staff role — their home is the community they lead, not
        // the applicant dashboard.
        if (! $user->isTutor() && ! $user->canAccessAdminPanel()
            && ! $user->isSupervisor() && ! $user->supervisesInterns()
            && $user->canAccessTacAdmin()) {
            return redirect()->route('admin.community.dashboard');
        }

        $applications = $user->applications()->with(['program', 'interview'])->latest()->get();
        $registrations = $user->registrations()->with('event')->latest()->get();

        // Get pending interviews for accepted applications
        $acceptedApplications = $applications->where('status', 'accepted');
        $programIds = $acceptedApplications->pluck('program_id')->unique();

        $availableInterviews = Interview::where('is_active', true)
            ->whereIn('program_id', $programIds)
            ->withCount('questions')
            ->get()
            ->map(function ($interview) use ($user) {
                $response = $interview->responses()
                    ->where('user_id', $user->id)
                    ->first();

                $interview->user_response = $response;

                return $interview;
            });

        // Get scheduled interviews from applications
        $scheduledInterviews = $applications
            ->whereNotNull('interview_id')
            ->map(function ($application) use ($user) {
                $interview = $application->interview;
                if (! $interview) {
                    return null;
                }

                $response = $interview->responses()
                    ->where('user_id', $user->id)
                    ->first();

                return [
                    'id' => $interview->id,
                    'title' => $interview->title,
                    'description' => $interview->description,
                    'passing_score' => $interview->passing_score,
                    'time_limit_minutes' => $interview->time_limit_minutes,
                    'questions_count' => $interview->questions()->count(),
                    'application_id' => $application->id,
                    'program_title' => $application->program?->title,
                    'interview_status' => $application->interview_status,
                    'interview_scheduled_at' => $application->interview_scheduled_at,
                    'user_response' => $response,
                ];
            })
            ->filter()
            ->values();

        $paymentSummaries = $this->buildPaymentSummaries($applications);

        // Outstanding balances first (most actionable), then most recent —
        // so the capped inline list surfaces what actually needs attention.
        $orderedSummaries = $paymentSummaries
            ->sortBy([
                fn ($a, $b) => ($a['remaining_amount'] > 0 ? 0 : 1) <=> ($b['remaining_amount'] > 0 ? 0 : 1),
                fn ($a, $b) => $b['application_id'] <=> $a['application_id'],
            ])
            ->values();

        return Inertia::render('Dashboard', [
            'user' => $user,
            'applications' => $applications,
            'registrations' => $registrations,
            'interviews' => $availableInterviews,
            'scheduledInterviews' => $scheduledInterviews,
            'paymentSummaries' => $orderedSummaries->take(self::INLINE_PAYMENT_LIMIT)->values(),
            'paymentSummariesTotal' => $orderedSummaries->count(),
        ]);
    }

    /**
     * Full "My Programs" list — every accepted application's payment
     * progress, without the dashboard's inline cap.
     */
    public function programs()
    {
        $user = auth()->user();

        $applications = $user->applications()->with(['program', 'interview'])->latest()->get();
        $paymentSummaries = $this->buildPaymentSummaries($applications)
            ->sortByDesc('application_id')
            ->values();

        return Inertia::render('Dashboard/Programs', [
            'paymentSummaries' => $paymentSummaries,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Application>  $applications
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildPaymentSummaries($applications)
    {
        return $applications
            ->where('status', 'accepted')
            ->map(function ($application) {
                $programPrice = (float) ($application->program?->price ?? 0);
                $maxInstallments = max(1, (int) ($application->program?->max_installments ?? 1));

                $successfulPayments = $application->payments()
                    ->where('status', 'successful')
                    ->orderBy('paid_at')
                    ->get(['id', 'amount', 'base_amount', 'status', 'paid_at', 'receipt_number']);

                $paidAmount = (float) $successfulPayments->sum('base_amount');
                $remainingAmount = max(0, round($programPrice - $paidAmount, 2));
                $installmentAmount = $maxInstallments > 0 ? round($programPrice / $maxInstallments, 2) : $programPrice;

                return [
                    'application_id' => $application->id,
                    'program_title' => $application->program?->title,
                    'program_slug' => $application->program?->slug,
                    'program_price' => $programPrice,
                    'max_installments' => $maxInstallments,
                    'installment_amount' => $installmentAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'completed_installments' => $successfulPayments->count(),
                    'status' => $programPrice <= 0
                        ? 'not-required'
                        : ($remainingAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially-paid' : 'unpaid')),
                    'checkout_url' => route('payments.checkout', $application),
                    'latest_receipt_url' => $successfulPayments->last()
                        ? route('payments.receipt', $successfulPayments->last()->id)
                        : null,
                    'payments' => $successfulPayments,
                ];
            })
            ->values();
    }
}
