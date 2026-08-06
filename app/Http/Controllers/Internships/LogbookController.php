<?php

namespace App\Http\Controllers\Internships;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Internships\Concerns\ResolvesActiveInternship;
use App\Models\LogbookEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LogbookController extends Controller
{
    use ResolvesActiveInternship;

    /**
     * Create or update today's logbook entry and submit it. Entries already
     * reviewed/approved by a supervisor are locked from further edits.
     */
    public function store(Request $request): RedirectResponse
    {
        $internship = $this->activeInternshipFor($request->user());
        $today = $this->todayFor($internship);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:10000'],
            'hours_spent' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'learnings' => ['nullable', 'string', 'max:5000'],
            'blockers' => ['nullable', 'string', 'max:5000'],
        ]);

        $entry = LogbookEntry::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', $today)
            ->first()
            ?? new LogbookEntry(['internship_id' => $internship->id, 'date' => $today]);

        if ($entry->exists && ! $entry->isEditableByIntern()) {
            return back()->with('warning', 'This logbook entry has already been reviewed and can no longer be edited.');
        }

        $entry->fill([
            'content' => $validated['content'],
            'hours_spent' => $validated['hours_spent'] ?? null,
            'learnings' => $validated['learnings'] ?? null,
            'blockers' => $validated['blockers'] ?? null,
            'status' => LogbookEntry::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);
        $entry->save();

        return back()->with('success', 'Logbook saved. You can now clock out.');
    }
}
