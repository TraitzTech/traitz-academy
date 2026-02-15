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

        return Inertia::render('Dashboard', [
            'user' => $user,
            'applications' => $applications,
            'registrations' => $registrations,
            'interviews' => $availableInterviews,
            'scheduledInterviews' => $scheduledInterviews,
        ]);
    }
}
