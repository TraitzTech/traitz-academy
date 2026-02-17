<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
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

        $paymentSummaries = $applications
            ->where('status', 'accepted')
            ->map(function ($application) {
                $programPrice = (float) ($application->program?->price ?? 0);
                $maxInstallments = max(1, (int) ($application->program?->max_installments ?? 1));

                $successfulPayments = $application->payments()
                    ->where('status', 'successful')
                    ->orderBy('paid_at')
                    ->get(['id', 'amount', 'status', 'paid_at', 'receipt_number']);

                $paidAmount = (float) $successfulPayments->sum('amount');
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

        return Inertia::render('Dashboard', [
            'user' => $user,
            'applications' => $applications,
            'registrations' => $registrations,
            'interviews' => $availableInterviews,
            'scheduledInterviews' => $scheduledInterviews,
            'paymentSummaries' => $paymentSummaries,
        ]);
    }
}
