<?php

namespace App\Http\Controllers\Community;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Community\Concerns\ResolvesCommunityMember;
use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacTrack;
use App\Notifications\Tac\NewCommunityMemberNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JoinController extends Controller
{
    use ResolvesCommunityMember;

    public function create(Request $request): Response
    {
        $member = $this->currentMember($request);

        return Inertia::render('Community/Join', [
            'tracks' => TacTrack::query()->active()->ordered()->get(['id', 'name', 'slug', 'tagline', 'icon', 'accent_color']),
            'statuses' => collect(CommunityMember::CURRENT_STATUS_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'memberCount' => CommunityMember::query()->active()->count(),

            // Somebody auto-included from an event registration lands here and
            // discovers they are already in — so show that rather than a blank
            // form that would create confusion about duplicate membership.
            'existingMember' => $member?->only(['id', 'first_name', 'email', 'joined_at']),
            'prefill' => $request->user() ? [
                'email' => $request->user()->email,
                'name' => $request->user()->name,
                'phone' => $request->user()->phone,
            ] : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'school' => ['nullable', 'string', 'max:255'],
            'current_status' => ['required', Rule::in(array_keys(CommunityMember::CURRENT_STATUS_LABELS))],
            'heard_about' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'track_ids' => ['required', 'array', 'min:1'],
            'track_ids.*' => ['integer', 'exists:tac_tracks,id'],
            'directory_opt_in' => ['boolean'],
        ], [
            'track_ids.required' => 'Pick at least one track you are interested in.',
        ]);

        $alreadyMember = CommunityMember::query()
            ->where('email', mb_strtolower(trim($validated['email'])))
            ->exists();

        $member = app(\App\Services\Tac\CommunityEnrollmentService::class)->record(
            email: $validated['email'],
            attributes: $validated,
            source: CommunityMember::SOURCE_JOIN_FORM,
            trackIds: $validated['track_ids'],
            notify: true,
        );

        if (! $member) {
            return back()->with('error', 'We could not read that email address. Please check it and try again.');
        }

        // The join form is where somebody states their own preferences, so it
        // is the one path allowed to set these directly.
        $member->update([
            'directory_opt_in' => (bool) ($validated['directory_opt_in'] ?? false),
            'current_status' => $validated['current_status'],
        ]);

        if ($alreadyMember) {
            return redirect()
                ->route('community.join')
                ->with('info', "You were already part of TAC, {$member->first_name} — we have updated your details and tracks.");
        }

        $this->notifyAdmins($member);

        return redirect()
            ->route('community.welcome')
            ->with('joined_member_id', $member->id)
            ->with('success', "Welcome to the community, {$member->first_name}!");
    }

    /**
     * A real confirmation page, not just a flash message — this is the moment
     * to point a new member at their tracks and the next thing happening.
     *
     * The member is read from the flashed session key rather than the URL, so
     * the page cannot be used to enumerate other people's details.
     */
    public function welcome(Request $request): Response|RedirectResponse
    {
        $memberId = $request->session()->get('joined_member_id');

        $member = $memberId
            ? CommunityMember::find($memberId)
            : $this->currentMember($request);

        if (! $member) {
            return redirect()->route('community.join');
        }

        $member->load('tracks:id,name,slug,tagline,accent_color');

        return Inertia::render('Community/Welcome', [
            'member' => $member->only(['id', 'first_name', 'full_name', 'email', 'joined_at']),
            'tracks' => $member->tracks,
            'upcoming' => \App\Models\TacActivity::query()
                ->upcoming()
                ->with('track:id,name,slug')
                ->orderBy('starts_at')
                ->take(3)
                ->get(['id', 'title', 'slug', 'type', 'starts_at', 'location', 'location_type', 'tac_track_id']),
            'whatsappLink' => SettingHelper::whatsAppCommunityLink(),
        ]);
    }

    private function notifyAdmins(CommunityMember $member): void
    {
        try {
            (new AnonymousNotifiable)
                ->route('mail', SettingHelper::contactEmail() ?? config('mail.from.address'))
                ->notify(new NewCommunityMemberNotification($member));
        } catch (\Throwable $e) {
            Log::warning('Could not notify admins of new TAC member', [
                'community_member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
