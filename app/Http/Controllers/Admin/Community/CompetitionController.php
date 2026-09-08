<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\TacActivity;
use App\Models\TacCompetitionEntry;
use App\Notifications\Tac\TacCompetitionResults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    /**
     * The judging room for a competition: every entry, the rubric, and this
     * judge's own scores alongside the running leaderboard.
     */
    public function show(Request $request, TacActivity $activity): Response
    {
        $this->authorize('judge', $activity);

        $activity->load('competitionCriteria');

        $entries = $activity->competitionEntries()
            ->with(['member:id,first_name,last_name,email,school', 'scores'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('total_score')
            ->orderBy('created_at')
            ->get()
            ->map(function (TacCompetitionEntry $entry) use ($request) {
                $mine = $entry->scores
                    ->where('judge_id', $request->user()->id)
                    ->keyBy('tac_competition_criterion_id')
                    ->map(fn ($s) => ['score' => (float) $s->score, 'comment' => $s->comment]);

                return [
                    ...$entry->only([
                        'id', 'title', 'description', 'repo_url', 'demo_url', 'video_url',
                        'attachment_path', 'team_name', 'team_members', 'status',
                        'submitted_at', 'total_score', 'rank', 'is_winner', 'award', 'judge_notes',
                    ]),
                    'member' => $entry->member,
                    'my_scores' => $mine,
                    'judge_count' => $entry->scores->pluck('judge_id')->unique()->count(),
                ];
            });

        return Inertia::render('Admin/Community/Competitions/Judge', [
            'activity' => $activity->only(['id', 'title', 'slug', 'type', 'status', 'starts_at', 'ends_at']),
            'criteria' => $activity->competitionCriteria,
            'entries' => $entries,
            'filters' => $request->only(['status']),
            'statuses' => collect(TacCompetitionEntry::STATUS_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'resultsPublished' => $activity->competitionEntries()->publishedResults()->exists(),
            'can' => ['publishResults' => $request->user()->hasTacExecutiveAuthority()],
        ]);
    }

    /**
     * Record this judge's scores for one entry against the whole rubric.
     */
    public function score(Request $request, TacActivity $activity, TacCompetitionEntry $entry): RedirectResponse
    {
        $this->authorize('judge', $activity);
        abort_unless((int) $entry->tac_activity_id === (int) $activity->id, 404);

        $validated = $request->validate([
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.criterion_id' => ['required', 'integer', 'exists:tac_competition_criteria,id'],
            'scores.*.score' => ['required', 'numeric', 'min:0'],
            'scores.*.comment' => ['nullable', 'string', 'max:1000'],
            'judge_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $criteria = $activity->competitionCriteria->keyBy('id');

        DB::transaction(function () use ($validated, $entry, $criteria, $request) {
            foreach ($validated['scores'] as $row) {
                $criterion = $criteria->get($row['criterion_id']);

                if (! $criterion) {
                    continue;
                }

                // A judge cannot award more than the criterion allows.
                $score = min((float) $row['score'], (float) $criterion->max_score);

                $entry->scores()->updateOrCreate(
                    [
                        'tac_competition_criterion_id' => $criterion->id,
                        'judge_id' => $request->user()->id,
                    ],
                    [
                        'score' => $score,
                        'comment' => $row['comment'] ?? null,
                    ],
                );
            }

            if (array_key_exists('judge_notes', $validated)) {
                $entry->forceFill(['judge_notes' => $validated['judge_notes']])->save();
            }

            $entry->recalculateScore();
        });

        $this->rerank($activity);

        return back()->with('success', "Scores saved for “{$entry->title}”.");
    }

    public function updateEntry(Request $request, TacActivity $activity, TacCompetitionEntry $entry): RedirectResponse
    {
        $this->authorize('judge', $activity);
        abort_unless((int) $entry->tac_activity_id === (int) $activity->id, 404);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(TacCompetitionEntry::STATUS_LABELS))],
            'is_winner' => ['boolean'],
            'award' => ['nullable', 'string', 'max:255'],
            'judge_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $entry->update($validated);
        $this->rerank($activity);

        return back()->with('success', "“{$entry->title}” updated.");
    }

    /**
     * Freeze the leaderboard, publish it, and tell every entrant how they did.
     */
    public function publishResults(Request $request, TacActivity $activity): RedirectResponse
    {
        $this->authorize('judge', $activity);
        abort_unless($request->user()->hasTacExecutiveAuthority(), 403);

        $validated = $request->validate([
            'outcome_summary' => ['nullable', 'string', 'max:5000'],
            'notify' => ['boolean'],
        ]);

        $this->rerank($activity);

        $entries = $activity->competitionEntries()
            ->where('status', '!=', TacCompetitionEntry::STATUS_DISQUALIFIED)
            ->with('member')
            ->get();

        $activity->update([
            'status' => TacActivity::STATUS_COMPLETED,
            'outcome_summary' => $validated['outcome_summary'] ?? $activity->outcome_summary,
        ]);

        $activity->competitionEntries()->update(['results_published_at' => now()]);

        $notified = 0;

        if ($validated['notify'] ?? false) {
            foreach ($entries as $entry) {
                if (! $entry->member?->isMailable()) {
                    continue;
                }

                $entry->member->notify(new TacCompetitionResults($entry->fresh()));
                $notified++;
            }
        }

        return back()->with('success', 'Results published.'.($notified > 0 ? " {$notified} entrant(s) notified." : ''));
    }

    /**
     * Assign ranks by score, highest first. Disqualified entries are excluded
     * so they never occupy a place on the leaderboard.
     */
    private function rerank(TacActivity $activity): void
    {
        $ranked = $activity->competitionEntries()
            ->where('status', '!=', TacCompetitionEntry::STATUS_DISQUALIFIED)
            ->whereNotNull('total_score')
            ->orderByDesc('total_score')
            ->orderBy('submitted_at')
            ->get();

        foreach ($ranked as $index => $entry) {
            $entry->forceFill(['rank' => $index + 1])->save();
        }

        $activity->competitionEntries()
            ->where('status', TacCompetitionEntry::STATUS_DISQUALIFIED)
            ->update(['rank' => null]);
    }
}
