<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\TacLeader;
use App\Models\TacLeaderPerformanceReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Staff-written performance reviews for a TAC leader — a rating and notes
 * for a period, visible to the leader themselves afterward. Append-only: a
 * mistaken entry is deleted and re-added rather than edited in place, so the
 * history stays honest.
 */
class LeaderPerformanceReviewController extends Controller
{
    public function store(Request $request, TacLeader $leader): RedirectResponse
    {
        $this->authorize('create', TacLeaderPerformanceReview::class);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'period_label' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $leader->performanceReviews()->create([
            ...$validated,
            'reviewed_by' => $request->user()->id,
        ]);

        return back()->with('success', "Review added for {$leader->name}.");
    }

    public function destroy(TacLeader $leader, TacLeaderPerformanceReview $review): RedirectResponse
    {
        $this->authorize('delete', $review);
        abort_unless((int) $review->tac_leader_id === (int) $leader->id, 404);

        $review->delete();

        return back()->with('success', 'Review removed.');
    }
}
