<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CohortController extends Controller
{
    public function index(Request $request): Response
    {
        $cohorts = Cohort::query()
            ->with('programs:id,title')
            ->withCount('internships')
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Cohort $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'is_intake' => $c->is_intake,
                'start_date' => optional($c->start_date)->toDateString(),
                'end_date' => optional($c->end_date)->toDateString(),
                'internships_count' => $c->internships_count,
                'programs' => $c->programs->pluck('title')->values(),
            ]);

        return Inertia::render('Admin/Internships/Cohorts/Index', [
            'cohorts' => $cohorts,
            'programs' => $this->programOptions(),
            'supervisors' => $this->supervisorOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCohort($request);
        $programs = $validated['programs'] ?? [];
        unset($validated['programs'], $validated['status']);

        $cohort = Cohort::query()->create([
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::random(5),
            'status' => Cohort::STATUS_UPCOMING,
        ]);

        $this->syncPrograms($cohort, $programs);
        $this->stampProgramWindows($cohort);
        $this->syncIntakeFlag($cohort);

        return back()->with('success', 'Cohort created.');
    }

    public function show(Cohort $cohort): Response
    {
        $cohort->load('programs:id,title');

        $programList = $cohort->programs->map(fn (Program $p) => [
            'id' => $p->id,
            'title' => $p->title,
            'supervisor_id' => $p->pivot->supervisor_id ? (int) $p->pivot->supervisor_id : null,
        ])->values();

        $programIds = $cohort->programs->pluck('id');

        $interns = $cohort->internships()
            ->with('intern:id,name,email', 'supervisor:id,name', 'program:id,title')
            ->get()
            ->map(fn (Internship $i) => [
                'id' => $i->id,
                'name' => $i->intern?->name,
                'email' => $i->intern?->email,
                'program' => $i->program?->title,
                'status' => $i->status,
                'supervisor' => $i->supervisor?->name,
                'effective_supervisor_id' => $i->effectiveSupervisorId(),
            ]);

        // Standalone (unassigned) interns whose program is run in this cohort.
        $available = Internship::query()
            ->whereNull('cohort_id')
            ->whereIn('program_id', $programIds)
            ->with('intern:id,name,email', 'program:id,title')
            ->get()
            ->map(fn (Internship $i) => [
                'id' => $i->id,
                'name' => $i->intern?->name,
                'email' => $i->intern?->email,
                'program' => $i->program?->title,
            ]);

        return Inertia::render('Admin/Internships/Cohorts/Show', [
            'cohort' => [
                'id' => $cohort->id,
                'name' => $cohort->name,
                'start_date' => optional($cohort->start_date)->toDateString(),
                'end_date' => optional($cohort->end_date)->toDateString(),
                'intake_opens_at' => optional($cohort->intake_opens_at)->toDateString(),
                'intake_closes_at' => optional($cohort->intake_closes_at)->toDateString(),
                'status' => $cohort->status,
                'is_intake' => $cohort->is_intake,
                'timezone' => $cohort->timezone,
                'programs' => $programList,
            ],
            'interns' => $interns,
            'available' => $available,
            'programs' => $this->programOptions(),
            'supervisors' => $this->supervisorOptions(),
        ]);
    }

    public function update(Request $request, Cohort $cohort): RedirectResponse
    {
        $validated = $this->validateCohort($request, $cohort);
        $programs = $validated['programs'] ?? [];
        unset($validated['programs']);
        $validated['status'] = $validated['status'] ?? $cohort->status;

        $cohort->update($validated);

        $this->syncPrograms($cohort, $programs);
        $this->stampProgramWindows($cohort);
        $this->syncIntakeFlag($cohort);

        return back()->with('success', 'Cohort updated.');
    }

    public function destroy(Cohort $cohort): RedirectResponse
    {
        // Interns are detached (cohort_id → null), not deleted, by the FK rule.
        $cohort->delete();

        return back()->with('success', 'Cohort deleted. Its interns are now unassigned.');
    }

    public function assignIntern(Request $request, Cohort $cohort): RedirectResponse
    {
        $validated = $request->validate([
            'internship_id' => ['required', 'exists:internships,id'],
        ]);

        $internship = Internship::query()->findOrFail($validated['internship_id']);

        abort_unless($cohort->hasProgram((int) $internship->program_id), 422, "This cohort does not run the intern's program.");

        $internship->update([
            'cohort_id' => $cohort->id,
            'start_date' => $internship->start_date ?? $cohort->start_date,
            'end_date' => $internship->end_date ?? $cohort->end_date,
        ]);

        return back()->with('success', 'Intern added to cohort.');
    }

    public function removeIntern(Cohort $cohort, Internship $internship): RedirectResponse
    {
        abort_unless((int) $internship->cohort_id === (int) $cohort->id, 404);

        $internship->update(['cohort_id' => null]);

        return back()->with('success', 'Intern removed from cohort.');
    }

    /**
     * Set a per-intern supervisor override (null clears it, falling back to the
     * program's supervisor in the cohort).
     */
    public function updateInternSupervisor(Request $request, Internship $internship): RedirectResponse
    {
        $validated = $request->validate([
            'supervisor_id' => ['nullable', 'exists:users,id'],
        ]);

        $internship->update(['supervisor_id' => $validated['supervisor_id'] ?? null]);

        return back()->with('success', 'Supervisor updated.');
    }

    private function validateCohort(Request $request, ?Cohort $cohort = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'intake_opens_at' => ['nullable', 'date'],
            'intake_closes_at' => ['nullable', 'date', 'after_or_equal:intake_opens_at'],
            'is_intake' => ['boolean'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'expected_hours_per_day' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'status' => ['sometimes', 'in:upcoming,active,completed,cancelled'],
            'programs' => ['array'],
            'programs.*.program_id' => ['required', 'exists:programs,id'],
            'programs.*.supervisor_id' => ['nullable', 'exists:users,id'],
        ]);
    }

    /**
     * @param  array<int, array{program_id: int|string, supervisor_id?: int|string|null}>  $programs
     */
    private function syncPrograms(Cohort $cohort, array $programs): void
    {
        $pivot = [];
        foreach ($programs as $program) {
            $pivot[(int) $program['program_id']] = [
                'supervisor_id' => $program['supervisor_id'] ?? null,
            ];
        }

        $cohort->programs()->sync($pivot);
    }

    /**
     * Push the cohort's intake window onto the application window of every
     * program it runs, so internship dates are entered once (on the cohort).
     * No-op when the cohort has no intake window set.
     */
    private function stampProgramWindows(Cohort $cohort): void
    {
        if ($cohort->intake_opens_at === null && $cohort->intake_closes_at === null) {
            return;
        }

        $programIds = $cohort->programs()->pluck('programs.id');
        if ($programIds->isEmpty()) {
            return;
        }

        Program::query()->whereIn('id', $programIds)->update([
            'applications_open_at' => $cohort->intake_opens_at,
            'applications_close_at' => $cohort->intake_closes_at,
        ]);
    }

    /**
     * Exactly one intake cohort at a time (the current batch, spanning programs).
     */
    private function syncIntakeFlag(Cohort $cohort): void
    {
        if (! $cohort->is_intake) {
            return;
        }

        Cohort::query()
            ->where('id', '!=', $cohort->id)
            ->where('is_intake', true)
            ->update(['is_intake' => false]);
    }

    private function programOptions()
    {
        return Program::query()->internships()->orderBy('title')->get(['id', 'title']);
    }

    private function supervisorOptions()
    {
        return User::query()
            ->whereIn('role', [
                User::ROLE_TUTOR,
                User::ROLE_SUPERVISOR,
                User::ROLE_PROGRAM_COORDINATOR,
                User::ROLE_CTO,
                User::ROLE_CEO,
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }
}
