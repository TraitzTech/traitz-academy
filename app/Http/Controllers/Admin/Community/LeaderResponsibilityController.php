<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\TacLeader;
use App\Models\TacLeaderResponsibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Academy staff assign each TAC leader concrete responsibilities beyond the
 * generic expectations of their role; the leader sees and updates the
 * progress of their own from their dashboard.
 */
class LeaderResponsibilityController extends Controller
{
    public function store(Request $request, TacLeader $leader): RedirectResponse
    {
        $this->authorize('create', TacLeaderResponsibility::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $leader->responsibilities()->create([
            ...$validated,
            'assigned_by' => $request->user()->id,
            'sort_order' => $leader->responsibilities()->count(),
        ]);

        return back()->with('success', "Responsibility assigned to {$leader->name}.");
    }

    public function update(Request $request, TacLeader $leader, TacLeaderResponsibility $responsibility): RedirectResponse
    {
        $this->authorize('update', $responsibility);
        $this->assertBelongsTo($leader, $responsibility);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
        ]);

        $responsibility->update($validated);

        return back()->with('success', 'Responsibility updated.');
    }

    /**
     * The leader's own affordance: move a responsibility along without
     * touching what it actually says.
     */
    public function updateStatus(Request $request, TacLeader $leader, TacLeaderResponsibility $responsibility): RedirectResponse
    {
        $this->authorize('updateStatus', $responsibility);
        $this->assertBelongsTo($leader, $responsibility);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(TacLeaderResponsibility::STATUS_LABELS))],
        ]);

        $responsibility->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === TacLeaderResponsibility::STATUS_COMPLETED
                ? ($responsibility->completed_at ?? now())
                : null,
        ]);

        return back()->with('success', 'Status updated.');
    }

    public function destroy(TacLeader $leader, TacLeaderResponsibility $responsibility): RedirectResponse
    {
        $this->authorize('delete', $responsibility);
        $this->assertBelongsTo($leader, $responsibility);

        $responsibility->delete();

        return back()->with('success', 'Responsibility removed.');
    }

    private function assertBelongsTo(TacLeader $leader, TacLeaderResponsibility $responsibility): void
    {
        abort_unless((int) $responsibility->tac_leader_id === (int) $leader->id, 404);
    }
}
