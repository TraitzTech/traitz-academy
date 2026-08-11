<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internships\Concerns\ResolvesActiveInternship;
use App\Models\LogbookEntry;
use App\Support\EmailContentSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogbookController extends Controller
{
    use ResolvesActiveInternship;

    /**
     * Today's entry (for the fill-in form) plus a paginated history of every
     * past entry for this internship.
     */
    public function index(Request $request): Response
    {
        $internship = $this->activeInternshipFor($request->user());
        $internship->loadMissing('program:id,title,office_days');
        $today = $this->todayFor($internship);

        // null when the program has no office schedule configured (we can't tell
        // office vs remote), true/false when it does.
        $todayIsOfficeDay = empty($internship->program?->office_days)
            ? null
            : $internship->program->isOfficeDay(\Carbon\Carbon::parse($today, $internship->timezone()));

        $todayEntry = LogbookEntry::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', $today)
            ->first();

        $entries = LogbookEntry::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', '!=', $today)
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();

        $entriesLogged = LogbookEntry::query()
            ->where('internship_id', $internship->id)
            ->whereNotNull('submitted_at')
            ->count();

        return Inertia::render('Internships/Logbook', [
            'internship' => [
                'id' => $internship->id,
                'program' => $internship->program?->title,
                'start_date' => optional($internship->start_date)->toDateString(),
            ],
            'today' => $today,
            'todayIsOfficeDay' => $todayIsOfficeDay,
            'entriesLogged' => $entriesLogged,
            'streak' => $this->currentStreak($internship, $today, (bool) $todayEntry?->submitted_at),
            'todayEntry' => $todayEntry ? [
                'content' => $todayEntry->content,
                'hours_spent' => $todayEntry->hours_spent,
                'learnings' => $todayEntry->learnings,
                'blockers' => $todayEntry->blockers,
                'solution' => $todayEntry->solution,
                'status' => $todayEntry->status,
                'supervisor_feedback' => $todayEntry->supervisor_feedback,
            ] : null,
            'entries' => $entries->through(fn (LogbookEntry $e) => [
                'id' => $e->id,
                'date' => $e->date->toDateString(),
                'content' => $e->content,
                'hours_spent' => $e->hours_spent,
                'blockers' => $e->blockers,
                'solution' => $e->solution,
                'status' => $e->status,
                'supervisor_feedback' => $e->supervisor_feedback,
            ]),
        ]);
    }

    /**
     * Consecutive working days (per config('internship.logbook.working_days'))
     * ending today (or yesterday, if today isn't submitted yet) with a
     * submitted entry. Capped at 60 lookback days as a sane safety bound.
     */
    private function currentStreak(\App\Models\Internship $internship, string $today, bool $todayIsSubmitted): int
    {
        $cursor = \Carbon\Carbon::parse($today, $internship->timezone());
        if (! $todayIsSubmitted) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;
        for ($i = 0; $i < 60; $i++) {
            if (! $internship->isWorkingDay($cursor)) {
                $cursor = $cursor->subDay();

                continue;
            }

            if (! $internship->hasLogbookEntryFor($cursor)) {
                break;
            }

            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    /**
     * Create or update today's logbook entry and submit it. Entries already
     * reviewed/approved by a supervisor are locked from further edits.
     */
    public function store(Request $request): RedirectResponse
    {
        $internship = $this->activeInternshipFor($request->user());
        $today = $this->todayFor($internship);

        $validated = $request->validate([
            // Rich-text (formatting + embedded screenshots), so allow more
            // room than plain text of the same visible content would need.
            'content' => ['required', 'string', 'max:50000'],
            'hours_spent' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'learnings' => ['nullable', 'string', 'max:5000'],
            // The form makes the intern answer yes/no; server-side it defaults
            // to "no difficulty" when absent. If yes, the difficulty itself is
            // required (its solution stays optional — still being worked on).
            'has_difficulty' => ['sometimes', 'boolean'],
            'blockers' => ['nullable', 'required_if:has_difficulty,true', 'string', 'max:5000'],
            'solution' => ['nullable', 'string', 'max:5000'],
        ]);

        // Intern-authored HTML is less trusted than staff-authored content
        // elsewhere in the app, so sanitize before it's ever persisted.
        $validated['content'] = EmailContentSanitizer::sanitize($validated['content']);
        abort_if($validated['content'] === '', 422, 'Logbook entry cannot be empty.');

        $entry = LogbookEntry::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', $today)
            ->first()
            ?? new LogbookEntry(['internship_id' => $internship->id, 'date' => $today]);

        if ($entry->exists && ! $entry->isEditableByIntern()) {
            return back()->with('warning', 'This logbook entry has already been reviewed and can no longer be edited.');
        }

        // No difficulty → clear both the problem and its solution.
        $hasDifficulty = (bool) ($validated['has_difficulty'] ?? false);

        $entry->fill([
            'content' => $validated['content'],
            'hours_spent' => $validated['hours_spent'] ?? null,
            'learnings' => $validated['learnings'] ?? null,
            'blockers' => $hasDifficulty ? ($validated['blockers'] ?? null) : null,
            'solution' => $hasDifficulty ? ($validated['solution'] ?? null) : null,
            'status' => LogbookEntry::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
        $entry->save();

        return back()->with('success', 'Logbook saved. You can now clock out.');
    }
}
