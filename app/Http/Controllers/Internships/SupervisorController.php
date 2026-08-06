<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipAttendance;
use App\Models\LogbookEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupervisorController extends Controller
{
    /**
     * Interns this user supervises (directly or via a cohort). Admin-panel staff
     * see every internship.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Internship::query()
            ->with(
                'intern:id,name,email',
                'program:id,title',
                'cohort:id,name',
                'cohort.programs:id,title',
                'supervisor:id,name',
            )
            ->withCount(['logbookEntries as pending_reviews' => fn ($q) => $q->where('status', LogbookEntry::STATUS_SUBMITTED)])
            ->latest('id');

        if (! $user->canAccessAdminPanel()) {
            $query->forSupervisor($user);
        }

        $records = $query->get();

        // Batch-resolve supervisor names (direct override or cohort/program
        // pivot) in one query instead of N+1 lookups per row.
        $supervisorIds = $records->map(fn (Internship $i) => $i->effectiveSupervisorId())->filter()->unique();
        $supervisorNames = User::query()->whereIn('id', $supervisorIds)->pluck('name', 'id');

        $interns = $records->map(fn (Internship $i) => [
            'id' => $i->id,
            'name' => $i->intern?->name,
            'email' => $i->intern?->email,
            'program' => $i->program?->title,
            'cohort' => $i->cohort?->name,
            'status' => $i->status,
            'pending_reviews' => $i->pending_reviews,
            'supervisor' => $supervisorNames->get($i->effectiveSupervisorId()),
            'start_date' => optional($i->start_date)->toDateString(),
        ]);

        return Inertia::render('Internships/Supervisor/Index', [
            'interns' => $interns,
            'viewAll' => $user->canAccessAdminPanel(),
        ]);
    }

    public function show(Request $request, Internship $internship): Response
    {
        $this->authorize('view', $internship);

        $internship->load('intern:id,name,email', 'program:id,title', 'cohort:id,name');

        $attendance = InternshipAttendance::query()
            ->where('internship_id', $internship->id)
            ->orderByDesc('date')
            ->limit(60)
            ->get()
            ->map(fn (InternshipAttendance $a) => [
                'id' => $a->id,
                'date' => $a->date->toDateString(),
                'clock_in_at' => optional($a->clock_in_at)->toIso8601String(),
                'clock_out_at' => optional($a->clock_out_at)->toIso8601String(),
                'hours' => $a->hours,
                'status' => $a->status,
            ]);

        $logbook = LogbookEntry::query()
            ->where('internship_id', $internship->id)
            ->orderByDesc('date')
            ->limit(60)
            ->get()
            ->map(fn (LogbookEntry $e) => [
                'id' => $e->id,
                'date' => $e->date->toDateString(),
                'content' => $e->content,
                'hours_spent' => $e->hours_spent,
                'learnings' => $e->learnings,
                'blockers' => $e->blockers,
                'status' => $e->status,
                'supervisor_feedback' => $e->supervisor_feedback,
            ]);

        return Inertia::render('Internships/Supervisor/Show', [
            'internship' => [
                'id' => $internship->id,
                'name' => $internship->intern?->name,
                'email' => $internship->intern?->email,
                'program' => $internship->program?->title,
                'cohort' => $internship->cohort?->name,
                'status' => $internship->status,
            ],
            'attendance' => $attendance,
            'logbook' => $logbook,
        ]);
    }

    public function reviewLogbook(Request $request, LogbookEntry $logbookEntry): RedirectResponse
    {
        $logbookEntry->loadMissing('internship.cohort');
        $this->authorize('review', $logbookEntry->internship);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,needs_revision'],
            'supervisor_feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $logbookEntry->update([
            'status' => $validated['status'],
            'supervisor_feedback' => $validated['supervisor_feedback'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Logbook reviewed.');
    }

    /**
     * Supervisor override of an attendance day (e.g. mark absent/excused).
     */
    public function markAttendance(Request $request, Internship $internship): RedirectResponse
    {
        $this->authorize('review', $internship);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,late,absent,excused'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $attendance = InternshipAttendance::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', $validated['date'])
            ->first()
            ?? new InternshipAttendance(['internship_id' => $internship->id, 'date' => $validated['date']]);

        $attendance->fill([
            'status' => $validated['status'],
            'note' => $validated['note'] ?? null,
            'source' => InternshipAttendance::SOURCE_SUPERVISOR,
            'marked_by' => $request->user()->id,
        ]);
        $attendance->save();

        return back()->with('success', 'Attendance updated.');
    }
}
