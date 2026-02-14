<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Notifications\ApplicationAcceptanceNotification;
use App\Notifications\BatchEmailNotification;
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

        return Inertia::render('Admin/Applications/Index', [
            'applications' => $applications,
            'filters' => $request->only(['search', 'status', 'program_id']),
            'stats' => $stats,
            'programs' => \App\Models\Program::select('id', 'title')->get(),
        ]);
    }

    public function show(Application $application): Response
    {
        $application->load(['program', 'user']);

        return Inertia::render('Admin/Applications/Show', [
            'application' => $application,
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

        $applications = Application::whereIn('id', $validated['ids'])->get();

        foreach ($applications as $application) {
            if ($validated['action'] === 'accept') {
                $application->update(['status' => 'accepted', 'reviewed_at' => now()]);
                if ($application->user) {
                    $application->user->notify(new ApplicationAcceptanceNotification($application));
                }
            } elseif ($validated['action'] === 'reject') {
                $application->update(['status' => 'rejected', 'reviewed_at' => now()]);
            } elseif ($validated['action'] === 'delete') {
                $application->delete();
            }
        }

        return back()->with('success', 'Bulk action completed successfully.');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $application->delete();

        return back()->with('success', 'Application deleted successfully.');
    }
}
