<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Community\Concerns\ResolvesCommunityMember;
use App\Http\Controllers\Controller;
use App\Models\TacActivity;
use App\Models\TacCompetitionEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Competition submissions. Entering requires a signed-in account: an entry can
 * be edited and withdrawn after the fact, so we need to know it is the same
 * person coming back — an email address in a form is not proof of that.
 */
class CompetitionEntryController extends Controller
{
    use ResolvesCommunityMember;

    public function store(Request $request, TacActivity $activity): RedirectResponse
    {
        abort_unless($activity->isCompetition(), 404);

        $member = $this->currentMember($request);

        if (! $member) {
            return back()->with('error', 'Sign in with your community account to enter this competition.');
        }

        $state = $activity->registrationState();

        if (! $state['open'] && $state['reason'] !== 'no_registration') {
            return back()->with('error', 'Submissions for this competition are closed.');
        }

        $validated = $this->validated($request);

        $existing = $activity->competitionEntries()
            ->where('community_member_id', $member->id)
            ->first();

        if ($existing) {
            return back()->with('info', 'You have already entered. Edit your existing submission instead.');
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('community/competition-entries', 'public');
        }

        unset($validated['attachment']);

        $activity->competitionEntries()->create([
            ...$validated,
            'community_member_id' => $member->id,
            'status' => TacCompetitionEntry::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $member->recordEngagement(5);

        return back()->with('success', 'Your entry has been submitted. Good luck!');
    }

    public function update(Request $request, TacActivity $activity, TacCompetitionEntry $entry): RedirectResponse
    {
        abort_unless($activity->isCompetition(), 404);
        abort_unless((int) $entry->tac_activity_id === (int) $activity->id, 404);

        $member = $this->currentMember($request);

        abort_unless($member && (int) $entry->community_member_id === (int) $member->id, 403);

        // Once judging has produced a score, the entry is frozen — otherwise
        // an entrant could swap the work out from under the judges.
        if ($entry->total_score !== null || $entry->results_published_at !== null) {
            return back()->with('error', 'Judging has started — this entry can no longer be edited.');
        }

        $validated = $this->validated($request);

        if ($request->hasFile('attachment')) {
            if ($entry->attachment_path) {
                Storage::disk('public')->delete($entry->attachment_path);
            }

            $validated['attachment_path'] = $request->file('attachment')->store('community/competition-entries', 'public');
        }

        unset($validated['attachment']);

        $entry->update([...$validated, 'submitted_at' => now()]);

        return back()->with('success', 'Your entry has been updated.');
    }

    public function destroy(Request $request, TacActivity $activity, TacCompetitionEntry $entry): RedirectResponse
    {
        $member = $this->currentMember($request);

        abort_unless($member && (int) $entry->community_member_id === (int) $member->id, 403);

        if ($entry->results_published_at !== null) {
            return back()->with('error', 'Results have been published — entries can no longer be withdrawn.');
        }

        if ($entry->attachment_path) {
            Storage::disk('public')->delete($entry->attachment_path);
        }

        $entry->delete();

        return back()->with('success', 'Your entry has been withdrawn.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'repo_url' => ['nullable', 'url', 'max:500'],
            'demo_url' => ['nullable', 'url', 'max:500'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'team_members' => ['nullable', 'array', 'max:10'],
            'team_members.*' => ['string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,zip,png,jpg,jpeg,doc,docx,ppt,pptx'],
        ]);
    }
}
