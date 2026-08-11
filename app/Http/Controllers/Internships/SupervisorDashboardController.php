<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\LogbookEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupervisorDashboardController extends Controller
{
    /**
     * A supervisor's home: everything they log in to act on — pending logbook
     * reviews, interns falling behind, and who's in today — aggregated across
     * every intern they supervise (directly or via a cohort program).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless(
            $user?->isSupervisor() || $user?->supervisesInterns() || $user?->canAccessAdminPanel(),
            403,
        );

        $internships = Internship::query()
            ->forSupervisor($user)
            ->with('intern:id,name,email', 'program:id,title', 'cohort:id,name,timezone')
            ->withCount(['logbookEntries as pending_reviews' => fn ($q) => $q->where('status', LogbookEntry::STATUS_SUBMITTED)])
            ->get();

        $today = now()->toDateString();

        $rows = $internships->map(function (Internship $i) use ($today) {
            $todayAttendance = $i->attendance()->whereDate('date', $today)->first();

            return [
                'id' => $i->id,
                'name' => $i->intern?->name,
                'email' => $i->intern?->email,
                'program' => $i->program?->title,
                'cohort' => $i->cohort?->name,
                'status' => $i->status,
                'pending_reviews' => (int) $i->pending_reviews,
                'missed_logbook_days' => $i->missedLogbookDaysCount(),
                'clocked_in_today' => $todayAttendance?->clock_in_at !== null,
            ];
        });

        // Interns needing attention first (most pending reviews, then most
        // missed days), so the top of the list is the day's work.
        $attention = $rows
            ->filter(fn ($r) => $r['pending_reviews'] > 0 || $r['missed_logbook_days'] > 0)
            ->sortByDesc(fn ($r) => [$r['pending_reviews'], $r['missed_logbook_days']])
            ->values()
            ->take(8);

        return Inertia::render('Internships/Supervisor/Dashboard', [
            'stats' => [
                'interns' => $rows->count(),
                'pending_reviews' => $rows->sum('pending_reviews'),
                'behind' => $rows->filter(fn ($r) => $r['missed_logbook_days'] > 0)->count(),
                'in_today' => $rows->filter(fn ($r) => $r['clocked_in_today'])->count(),
            ],
            'attention' => $attention,
        ]);
    }
}
