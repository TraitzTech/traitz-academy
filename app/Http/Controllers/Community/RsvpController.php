<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Community\Concerns\ResolvesCommunityMember;
use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacActivityRsvp;
use App\Notifications\Tac\TacRsvpConfirmation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RsvpController extends Controller
{
    use ResolvesCommunityMember;

    /**
     * RSVP to an activity. Open to anyone: registering for a workshop is
     * itself a way into the community, so a non-member is enrolled on the spot
     * rather than being bounced to a signup page first.
     */
    public function store(Request $request, TacActivity $activity): RedirectResponse
    {
        $state = $activity->registrationState();

        // "Full" is not a refusal — it routes to the waitlist below. Every
        // other closed reason genuinely ends the request here.
        if (! $state['open'] && $state['reason'] !== 'full') {
            return back()->with('error', $this->closedMessage($state['reason'], $activity));
        }

        // A signed-in member already has a name and email on file; only a guest
        // needs to supply them.
        $isGuest = $request->user() === null;

        $validated = $request->validate([
            'first_name' => [Rule::requiredIf($isGuest), 'nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => [Rule::requiredIf($isGuest), 'nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'school' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_phone' => ['nullable', 'string', 'max:40'],
        ]);

        $member = $this->resolveMember(
            $request,
            attributes: array_filter([
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'school' => $validated['school'] ?? null,
            ]),
            source: CommunityMember::SOURCE_EVENT,
        );

        if (! $member) {
            return back()->with('error', 'We need a valid email address to hold your place.');
        }

        if ($member->lifecycle_status === CommunityMember::LIFECYCLE_BLOCKED) {
            return back()->with('error', 'This account cannot register for community activities.');
        }

        $existing = $activity->rsvps()->where('community_member_id', $member->id)->first();

        if ($existing && $existing->status !== TacActivityRsvp::STATUS_CANCELLED) {
            return back()->with('info', "You are already registered for “{$activity->title}”.");
        }

        $rsvp = DB::transaction(function () use ($activity, $member, $existing, $validated) {
            // Re-check capacity inside the transaction: two people can pass the
            // earlier check at once and both take the last seat otherwise.
            $taken = $activity->rsvps()
                ->whereNot('status', TacActivityRsvp::STATUS_CANCELLED)
                ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
                ->count();

            $full = $activity->capacity !== null && $taken >= $activity->capacity;

            $attributes = [
                'status' => $full ? TacActivityRsvp::STATUS_WAITLISTED : TacActivityRsvp::STATUS_REGISTERED,
                'payment_status' => $activity->is_paid
                    ? TacActivityRsvp::PAYMENT_PENDING
                    : TacActivityRsvp::PAYMENT_FREE,
                'amount' => $activity->is_paid ? $activity->price : 0,
                'currency' => $activity->currency,
                'payment_phone' => $validated['payment_phone'] ?? null,
                'note' => $validated['note'] ?? null,
            ];

            $rsvp = $existing
                ? tap($existing)->update($attributes)
                : $activity->rsvps()->create([...$attributes, 'community_member_id' => $member->id]);

            $activity->syncRsvpCount();

            return $rsvp;
        });

        $member->recordEngagement(2);

        // A guest with no account needs a way back to *their* reservation at
        // checkout without exposing the RSVP id in a URL anyone could guess.
        if (! $request->user()) {
            $request->session()->put("tac_rsvp.{$activity->id}", $rsvp->id);
        }

        if ($member->isMailable()) {
            $member->notify(new TacRsvpConfirmation($rsvp->fresh(['activity', 'member'])));
        }

        if ($rsvp->status === TacActivityRsvp::STATUS_WAITLISTED) {
            return back()->with('info', "“{$activity->title}” is full — you are on the waitlist and we will email you if a place opens.");
        }

        if ($activity->is_paid) {
            return redirect()
                ->route('community.activities.checkout', $activity)
                ->with('success', 'Your place is held. Complete payment to confirm it.');
        }

        return back()->with('success', "You are registered for “{$activity->title}”. Check your email for the details.");
    }

    public function destroy(Request $request, TacActivity $activity): RedirectResponse
    {
        $member = $this->currentMember($request);

        if (! $member) {
            return back()->with('error', 'Sign in to manage your RSVPs.');
        }

        $rsvp = $activity->rsvps()->where('community_member_id', $member->id)->first();

        if (! $rsvp) {
            return back()->with('info', 'You were not registered for this activity.');
        }

        $rsvp->update(['status' => TacActivityRsvp::STATUS_CANCELLED]);
        $activity->syncRsvpCount();

        $this->promoteFromWaitlist($activity);

        return back()->with('success', "Your RSVP for “{$activity->title}” has been cancelled.");
    }

    /**
     * A cancellation frees a seat, so the next waitlisted member takes it and
     * is told immediately — otherwise a full activity stays full on paper
     * while seats sit empty in the room.
     */
    private function promoteFromWaitlist(TacActivity $activity): void
    {
        if ($activity->capacity === null) {
            return;
        }

        $seatsFree = $activity->capacity - $activity->rsvps()
            ->whereIn('status', [
                TacActivityRsvp::STATUS_REGISTERED,
                TacActivityRsvp::STATUS_CONFIRMED,
                TacActivityRsvp::STATUS_ATTENDED,
            ])->count();

        if ($seatsFree <= 0) {
            return;
        }

        $activity->rsvps()
            ->where('status', TacActivityRsvp::STATUS_WAITLISTED)
            ->with('member')
            ->orderBy('created_at')
            ->take($seatsFree)
            ->get()
            ->each(function (TacActivityRsvp $rsvp) {
                $rsvp->update(['status' => TacActivityRsvp::STATUS_REGISTERED]);

                if ($rsvp->member?->isMailable()) {
                    $rsvp->member->notify(new TacRsvpConfirmation($rsvp->fresh(['activity', 'member'])));
                }
            });

        $activity->syncRsvpCount();
    }

    private function closedMessage(?string $reason, TacActivity $activity): string
    {
        return match ($reason) {
            'full' => "“{$activity->title}” has reached capacity.",
            'closed' => 'Registration for this activity has closed.',
            'not_yet_open' => 'Registration has not opened yet — check back soon.',
            'past' => 'This activity has already taken place.',
            'cancelled' => 'This activity has been cancelled.',
            'no_registration' => 'This activity does not need registration — just show up.',
            default => 'Registration is not open for this activity.',
        };
    }
}
