<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Interview;
use App\Notifications\ApplicationAcceptanceNotification;
use App\Notifications\BatchEmailNotification;
use App\Notifications\InterviewInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Application::with(['program', 'user']);

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

        $applications = $query->latest()
            ->paginate(15)
            ->withQueryString();

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
            'filters' => $request->only(['search', 'status', 'program_id']),
            'stats' => $stats,
            'programs' => \App\Models\Program::select('id', 'title')->get(),
            'interviews' => $interviews,
        ]);
    }

    public function show(Application $application): Response
    {
        $application->load(['program', 'user', 'interview']);

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
        ]);
    }

    public function accept(Application $application): RedirectResponse
    {
        $application->update([
            'status' => 'accepted',
            'reviewed_at' => now(),
        ]);

        // Notify the applicant with acceptance notification (includes WhatsApp community link)
        if ($application->user) {
            $application->user->notify(new ApplicationAcceptanceNotification($application));
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
                $application->update(['status' => 'accepted', 'reviewed_at' => now()]);
                if ($application->user) {
                    $application->user->notify(new ApplicationAcceptanceNotification($application));
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
}
