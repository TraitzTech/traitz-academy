<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\TacActivity;
use App\Models\TacActivityRsvp;
use App\Notifications\Tac\TacActivityReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RsvpController extends Controller
{
    public function update(Request $request, TacActivity $activity, TacActivityRsvp $rsvp): RedirectResponse
    {
        $this->authorize('manageRsvps', $activity);
        abort_unless((int) $rsvp->tac_activity_id === (int) $activity->id, 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(TacActivityRsvp::STATUS_LABELS))],
            'payment_status' => ['nullable', Rule::in(['free', 'pending', 'paid', 'failed', 'refunded'])],
        ]);

        $rsvp->update([
            ...$validated,
            'checked_in_at' => $validated['status'] === TacActivityRsvp::STATUS_ATTENDED
                ? ($rsvp->checked_in_at ?? now())
                : $rsvp->checked_in_at,
            'paid_at' => ($validated['payment_status'] ?? null) === TacActivityRsvp::PAYMENT_PAID
                ? ($rsvp->paid_at ?? now())
                : $rsvp->paid_at,
        ]);

        $activity->syncRsvpCount();

        // Turning up is the strongest engagement signal we have.
        if ($validated['status'] === TacActivityRsvp::STATUS_ATTENDED) {
            $rsvp->member?->recordEngagement(3);
        }

        return back()->with('success', 'RSVP updated.');
    }

    public function bulk(Request $request, TacActivity $activity): RedirectResponse
    {
        $this->authorize('manageRsvps', $activity);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['status', 'remind', 'delete'])],
            'status' => ['nullable', Rule::in(array_keys(TacActivityRsvp::STATUS_LABELS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $rsvps = $activity->rsvps()->whereIn('id', $validated['ids'])->with('member')->get();
        $count = $rsvps->count();

        switch ($validated['action']) {
            case 'status':
                abort_unless(filled($validated['status'] ?? null), 422);

                foreach ($rsvps as $rsvp) {
                    $rsvp->update([
                        'status' => $validated['status'],
                        'checked_in_at' => $validated['status'] === TacActivityRsvp::STATUS_ATTENDED
                            ? ($rsvp->checked_in_at ?? now())
                            : $rsvp->checked_in_at,
                    ]);

                    if ($validated['status'] === TacActivityRsvp::STATUS_ATTENDED) {
                        $rsvp->member?->recordEngagement(3);
                    }
                }

                $message = "{$count} RSVP(s) updated.";
                break;

            case 'remind':
                $sent = 0;

                foreach ($rsvps as $rsvp) {
                    if (! $rsvp->member?->isMailable() || $rsvp->status === TacActivityRsvp::STATUS_CANCELLED) {
                        continue;
                    }

                    $rsvp->member->notify(new TacActivityReminder($rsvp, $validated['note'] ?? null));
                    $rsvp->update(['reminded_at' => now()]);
                    $sent++;
                }

                $message = $sent > 0
                    ? "Reminder sent to {$sent} member(s)."
                    : 'No reminders sent — those members have opted out or cancelled.';
                break;

            default:
                $activity->rsvps()->whereIn('id', $validated['ids'])->delete();
                $message = "{$count} RSVP(s) removed.";
        }

        $activity->syncRsvpCount();

        return back()->with('success', $message);
    }

    /**
     * Remind everyone still expected at an activity.
     */
    public function remindAll(Request $request, TacActivity $activity): RedirectResponse
    {
        $this->authorize('manageRsvps', $activity);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sent = 0;

        $activity->rsvps()
            ->whereIn('status', [TacActivityRsvp::STATUS_REGISTERED, TacActivityRsvp::STATUS_CONFIRMED])
            ->with('member')
            ->chunkById(100, function ($rsvps) use ($validated, &$sent) {
                foreach ($rsvps as $rsvp) {
                    if (! $rsvp->member?->isMailable()) {
                        continue;
                    }

                    $rsvp->member->notify(new TacActivityReminder($rsvp, $validated['note'] ?? null));
                    $rsvp->update(['reminded_at' => now()]);
                    $sent++;
                }
            });

        return back()->with(
            $sent > 0 ? 'success' : 'info',
            $sent > 0 ? "Reminder sent to {$sent} member(s)." : 'Nobody to remind yet.',
        );
    }

    public function export(TacActivity $activity): StreamedResponse
    {
        $this->authorize('manageRsvps', $activity);

        $rsvps = $activity->rsvps()->with('member')->latest()->get();
        $filename = 'tac-'.$activity->slug.'-rsvps.csv';

        return response()->streamDownload(function () use ($rsvps) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Phone', 'School', 'Status', 'Payment', 'Amount', 'Registered at', 'Checked in']);

            foreach ($rsvps as $rsvp) {
                fputcsv($handle, [
                    $rsvp->member?->full_name,
                    $rsvp->member?->email,
                    $rsvp->member?->phone,
                    $rsvp->member?->school,
                    $rsvp->statusLabel(),
                    $rsvp->payment_status,
                    $rsvp->amount,
                    $rsvp->created_at?->format('Y-m-d H:i'),
                    $rsvp->checked_in_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
