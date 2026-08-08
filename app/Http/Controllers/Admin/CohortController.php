<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;
use App\Notifications\AdminAccountCredentialsNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $validated['timezone'] = ($validated['timezone'] ?? null) ?: config('app.timezone', 'UTC');

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

        // Users already in this cohort — excluded from the pool below so we
        // never surface a candidate that would collide with the
        // unique(cohort_id, user_id) constraint on assignment.
        $memberUserIds = $cohort->internships()->pluck('user_id');

        // Standalone (unassigned) interns whose program is run in this cohort.
        // Surfaces when they were accepted so an admin can filter down to a
        // specific batch (e.g. everyone accepted before cohorts existed).
        $available = Internship::query()
            ->whereNull('cohort_id')
            ->whereIn('program_id', $programIds)
            ->whereNotIn('user_id', $memberUserIds)
            ->with('intern:id,name,email', 'program:id,title', 'application:id,reviewed_at')
            ->get()
            ->map(fn (Internship $i) => [
                'id' => $i->id,
                'name' => $i->intern?->name,
                'email' => $i->intern?->email,
                'program' => $i->program?->title,
                'accepted_at' => optional($i->application?->reviewed_at ?? $i->created_at)->toDateString(),
            ])
            ->sortBy('accepted_at')
            ->values();

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
        $validated['timezone'] = ($validated['timezone'] ?? null) ?: $cohort->timezone ?: config('app.timezone', 'UTC');

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
            'internship_ids' => ['required', 'array', 'min:1'],
            'internship_ids.*' => ['integer', 'exists:internships,id'],
        ]);

        $internships = Internship::query()
            ->whereNull('cohort_id')
            ->whereIn('id', $validated['internship_ids'])
            ->with('intern:id,name')
            ->get();

        foreach ($internships as $internship) {
            abort_unless($cohort->hasProgram((int) $internship->program_id), 422, "This cohort does not run {$internship->intern?->name}'s program.");
        }

        // Guard the unique(cohort_id, user_id) constraint: skip anyone already
        // in this cohort (via another program, a manual add, or a duplicate
        // standalone record) rather than letting the DB throw.
        $memberUserIds = $cohort->internships()->pluck('user_id')->all();

        // Logbook expectations begin the day they're placed — so interns
        // onboarded mid-stream don't accrue missed days for the past.
        $placedOn = $this->cohortToday($cohort);

        $added = DB::transaction(function () use ($internships, $cohort, &$memberUserIds, $placedOn) {
            $count = 0;
            foreach ($internships as $internship) {
                if (in_array($internship->user_id, $memberUserIds, true)) {
                    continue;
                }

                $internship->update([
                    'cohort_id' => $cohort->id,
                    'start_date' => $internship->start_date ?? $cohort->start_date,
                    'logbook_starts_on' => $internship->logbook_starts_on ?? $placedOn,
                    'end_date' => $internship->end_date ?? $cohort->end_date,
                ]);
                $memberUserIds[] = $internship->user_id;
                $count++;
            }

            return $count;
        });

        $skipped = $internships->count() - $added;

        if ($added === 0) {
            return back()->with('warning', 'No interns added — they are already in this cohort.');
        }

        $message = $added === 1 ? 'Intern added to cohort.' : "{$added} interns added to cohort.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (already in this cohort).";
        }

        return back()->with('success', $message);
    }

    /**
     * Manually create an Internship for someone who never went through the
     * applications pipeline — either an existing user, or a brand-new
     * account (credentials emailed the same way Admin/Users does it).
     */
    public function createIntern(Request $request, Cohort $cohort): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'mode' => ['required', 'in:existing,new'],
            'user_id' => ['required_if:mode,existing', 'nullable', 'integer', 'exists:users,id'],
            'name' => ['required_if:mode,new', 'nullable', 'string', 'max:255'],
            'email' => ['required_if:mode,new', 'nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        abort_unless($cohort->hasProgram((int) $validated['program_id']), 422, 'This cohort does not run that program.');
        $programId = (int) $validated['program_id'];

        // Existing user: place them directly, guarding the duplicate up front.
        if ($validated['mode'] === 'existing') {
            $user = User::findOrFail($validated['user_id']);

            if (Internship::where('cohort_id', $cohort->id)->where('user_id', $user->id)->exists()) {
                return back()->with('error', "{$user->name} is already an intern in this cohort.");
            }

            DB::transaction(fn () => $this->placeIntern($cohort, $user->id, $programId));

            return back()->with('success', "{$user->name} added as an intern.");
        }

        // New person: create the account and internship atomically, then email
        // credentials only after the transaction commits (so a failed insert
        // never leaves an orphaned account that already got a welcome email).
        if (User::where('email', $validated['email'])->exists()) {
            return back()->with('error', 'A user with that email already exists — search for them instead.');
        }

        $plainPassword = (string) Str::password(12);

        $user = DB::transaction(function () use ($validated, $plainPassword, $cohort, $programId) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($plainPassword),
                'role' => User::ROLE_USER,
            ]);

            $this->placeIntern($cohort, $user->id, $programId);

            return $user;
        });

        $user->notify(new AdminAccountCredentialsNotification(
            temporaryPassword: $plainPassword,
            createdBy: $request->user(),
        ));

        return back()->with('success', "{$user->name} added as an intern.");
    }

    /**
     * Lightweight user lookup for the "existing user" picker in the manual
     * add-intern form. Scoped under admin/internships (not the executive-only
     * Admin/Users routes) since any cohort admin should be able to use it.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $users = User::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
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

    /**
     * Create an active internship placing a user into this cohort under a
     * program. Logbook expectations begin today (not the cohort's start date),
     * so a mid-stream onboarding isn't retroactively behind.
     */
    private function placeIntern(Cohort $cohort, int $userId, int $programId): Internship
    {
        return Internship::create([
            'cohort_id' => $cohort->id,
            'program_id' => $programId,
            'user_id' => $userId,
            'start_date' => $cohort->start_date,
            'logbook_starts_on' => $this->cohortToday($cohort),
            'end_date' => $cohort->end_date,
            'status' => Internship::STATUS_ACTIVE,
        ]);
    }

    /**
     * Today's date in the cohort's timezone — used to stamp when logbook
     * expectations begin for interns placed into this cohort.
     */
    private function cohortToday(Cohort $cohort): string
    {
        return \Carbon\Carbon::now($cohort->timezone ?: config('app.timezone', 'UTC'))->toDateString();
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
