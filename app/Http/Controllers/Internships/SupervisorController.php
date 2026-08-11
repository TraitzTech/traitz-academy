<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
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
            'missed_logbook_days' => $i->missedLogbookDaysCount(),
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

        $internship->load('intern:id,name,email', 'program:id,title', 'cohort:id,name,timezone');

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

        $workingDaysElapsed = $internship->workingDaysElapsed();
        $submittedLogbookDays = $internship->submittedLogbookDaysCount();
        $missedLogbookDays = max(0, $workingDaysElapsed - $submittedLogbookDays);

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
            'compliance' => [
                'working_days_elapsed' => $workingDaysElapsed,
                'logbook_entries_submitted' => $submittedLogbookDays,
                'missed_logbook_days' => $missedLogbookDays,
            ],
        ]);
    }

    /**
     * Read-only list of cohorts this user has interns in (directly or via a
     * program they supervise). Full cohort management (create/edit/assign)
     * stays admin-only — this is just visibility into dates and rosters.
     */
    public function cohorts(Request $request): Response
    {
        $user = $request->user();

        $cohortIds = $this->supervisedCohortIds($user);

        $cohorts = Cohort::query()
            ->whereIn('id', $cohortIds)
            ->with('programs:id,title')
            ->withCount(['internships as my_interns_count' => fn ($q) => $q->forSupervisor($user)])
            ->latest('start_date')
            ->get()
            ->map(fn (Cohort $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'is_intake' => $c->is_intake,
                'start_date' => optional($c->start_date)->toDateString(),
                'end_date' => optional($c->end_date)->toDateString(),
                'intake_opens_at' => optional($c->intake_opens_at)->toDateString(),
                'intake_closes_at' => optional($c->intake_closes_at)->toDateString(),
                'my_interns_count' => $c->my_interns_count,
                'programs' => $c->programs->pluck('title')->values(),
            ]);

        return Inertia::render('Internships/Supervisor/Cohorts/Index', [
            'cohorts' => $cohorts,
        ]);
    }

    public function cohortShow(Request $request, Cohort $cohort): Response
    {
        $user = $request->user();
        abort_unless($this->supervisedCohortIds($user)->contains($cohort->id), 403);

        $cohort->load('programs:id,title');

        $interns = Internship::query()
            ->where('cohort_id', $cohort->id)
            ->forSupervisor($user)
            ->with('intern:id,name,email', 'program:id,title')
            ->get()
            ->map(fn (Internship $i) => [
                'id' => $i->id,
                'name' => $i->intern?->name,
                'email' => $i->intern?->email,
                'program' => $i->program?->title,
                'status' => $i->status,
                'start_date' => optional($i->start_date)->toDateString(),
            ]);

        return Inertia::render('Internships/Supervisor/Cohorts/Show', [
            'cohort' => [
                'id' => $cohort->id,
                'name' => $cohort->name,
                'description' => $cohort->description,
                'start_date' => optional($cohort->start_date)->toDateString(),
                'end_date' => optional($cohort->end_date)->toDateString(),
                'intake_opens_at' => optional($cohort->intake_opens_at)->toDateString(),
                'intake_closes_at' => optional($cohort->intake_closes_at)->toDateString(),
                'status' => $cohort->status,
                'is_intake' => $cohort->is_intake,
                'programs' => $cohort->programs->pluck('title')->values(),
            ],
            'interns' => $interns,
        ]);
    }

    /**
     * Cohort IDs where this user supervises at least one intern — directly
     * assigned or via the program's cohort-pivot supervisor.
     */
    private function supervisedCohortIds(User $user): \Illuminate\Support\Collection
    {
        return Internship::query()
            ->forSupervisor($user)
            ->whereNotNull('cohort_id')
            ->pluck('cohort_id')
            ->unique()
            ->values();
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
