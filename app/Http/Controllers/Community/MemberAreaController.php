<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Community\Concerns\ResolvesCommunityMember;
use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacActivityRsvp;
use App\Models\TacTrack;
use App\Services\Tac\CommunityEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MemberAreaController extends Controller
{
    use ResolvesCommunityMember;

    public function dashboard(Request $request): Response|RedirectResponse
    {
        $member = $this->requireMember($request);

        if ($member instanceof RedirectResponse) {
            return $member;
        }

        $member->load('tracks:id,name,slug,tagline,accent_color', 'leadership.track:id,name');

        $trackIds = $member->tracks->pluck('id');

        return Inertia::render('Community/Member/Dashboard', [
            'member' => $member,
            'upcomingRsvps' => $member->rsvps()
                ->whereIn('status', [TacActivityRsvp::STATUS_REGISTERED, TacActivityRsvp::STATUS_CONFIRMED, TacActivityRsvp::STATUS_WAITLISTED])
                ->whereHas('activity', fn ($q) => $q->where('starts_at', '>=', now()))
                ->with('activity.track:id,name,slug,accent_color')
                ->get(),
            'pastRsvps' => $member->rsvps()
                ->whereHas('activity', fn ($q) => $q->where('starts_at', '<', now()))
                ->with('activity:id,title,slug,type,starts_at')
                ->latest()
                ->take(10)
                ->get(),
            'entries' => $member->competitionEntries()
                ->with('activity:id,title,slug,starts_at')
                ->latest()
                ->get(),

            // Recommendations from the member's own tracks — the reason to
            // pick tracks in the first place.
            'recommended' => TacActivity::query()
                ->upcoming()
                ->when($trackIds->isNotEmpty(), fn ($q) => $q->whereIn('tac_track_id', $trackIds))
                ->whereDoesntHave('rsvps', fn ($q) => $q->where('community_member_id', $member->id))
                ->with('track:id,name,slug,accent_color')
                ->orderBy('starts_at')
                ->take(4)
                ->get(),

            'stats' => [
                'attended' => $member->rsvps()->where('status', TacActivityRsvp::STATUS_ATTENDED)->count(),
                'upcoming' => $member->rsvps()
                    ->whereIn('status', [TacActivityRsvp::STATUS_REGISTERED, TacActivityRsvp::STATUS_CONFIRMED])
                    ->whereHas('activity', fn ($q) => $q->where('starts_at', '>=', now()))
                    ->count(),
                'entries' => $member->competitionEntries()->count(),
                'wins' => $member->competitionEntries()->where('is_winner', true)->count(),
                'member_since' => $member->joined_at?->toDateString(),
                'engagement_score' => $member->engagement_score,
            ],
        ]);
    }

    public function profile(Request $request): Response|RedirectResponse
    {
        $member = $this->requireMember($request);

        if ($member instanceof RedirectResponse) {
            return $member;
        }

        $member->load('tracks:id,name,slug');

        return Inertia::render('Community/Member/Profile', [
            'member' => $member,
            'selectedTrackIds' => $member->tracks->pluck('id'),
            'tracks' => TacTrack::query()->active()->ordered()->get(['id', 'name', 'slug', 'tagline', 'icon', 'accent_color']),
            'statuses' => collect(CommunityMember::CURRENT_STATUS_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $member = $this->requireMember($request);

        if ($member instanceof RedirectResponse) {
            return $member;
        }

        // Drop blank rows from the dynamic social-links editor before
        // validating — members may list any platform under any label.
        $socialLinks = collect($request->input('social_links', []))
            ->filter(fn ($url, $label) => filled($url) && filled($label))
            ->all();
        $request->merge(['social_links' => $socialLinks]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'school' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'current_status' => ['required', Rule::in(array_keys(CommunityMember::CURRENT_STATUS_LABELS))],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'max:255', 'url'],
            'directory_opt_in' => ['boolean'],
            'email_opt_in' => ['boolean'],
            'track_ids' => ['required', 'array', 'min:1'],
            'track_ids.*' => ['integer', 'exists:tac_tracks,id'],
            'avatar' => ['nullable', 'image', 'max:4096'],
        ], [
            'track_ids.required' => 'Keep at least one track selected.',
        ]);

        $trackIds = $validated['track_ids'];
        unset($validated['track_ids'], $validated['avatar']);

        if ($request->hasFile('avatar')) {
            if ($member->avatar_path) {
                Storage::disk('public')->delete($member->avatar_path);
            }

            $validated['avatar_path'] = $request->file('avatar')->store('community/avatars', 'public');
        }

        $member->update($validated);
        $member->tracks()->sync($this->trackSyncPayload($member, $trackIds));

        return back()->with('success', 'Your profile has been updated.');
    }

    /**
     * The opt-in member directory: only people who chose to be listed, and only
     * visible to somebody who is themselves a member.
     */
    public function directory(Request $request): Response|RedirectResponse
    {
        $member = $this->requireMember($request);

        if ($member instanceof RedirectResponse) {
            return $member;
        }

        $query = CommunityMember::query()
            ->inDirectory()
            ->with('tracks:id,name,slug,accent_color')
            ->when($request->filled('search'), fn ($q) => $q->search($request->input('search')))
            ->when($request->filled('track'), fn ($q) => $q->whereHas(
                'tracks',
                fn ($t) => $t->where('tac_tracks.slug', $request->input('track'))
            ))
            ->when($request->filled('school'), fn ($q) => $q->where('school', $request->input('school')))
            ->orderBy('first_name');

        return Inertia::render('Community/Member/Directory', [
            'members' => $query->paginate(24)->withQueryString()->through(fn (CommunityMember $m) => [
                'id' => $m->id,
                'full_name' => $m->full_name,
                'school' => $m->school,
                'bio' => $m->bio,
                'avatar_path' => $m->avatar_path,
                'membership_status' => $m->membership_status,
                'status_label' => $m->statusLabel(),
                'social_links' => $m->social_links,
                'tracks' => $m->tracks,
                // Contact details are deliberately not exposed, even to
                // members — the directory is for finding people, not for
                // harvesting a mailing list.
            ]),
            'filters' => $request->only(['search', 'track', 'school']),
            'tracks' => TacTrack::query()->active()->ordered()->get(['id', 'name', 'slug', 'accent_color']),
            'schools' => CommunityMember::query()
                ->inDirectory()
                ->whereNotNull('school')
                ->where('school', '!=', '')
                ->distinct()
                ->orderBy('school')
                ->pluck('school'),
            'isListed' => $member->directory_opt_in,
        ]);
    }

    /**
     * Every member-area page needs a member. Somebody signed in with no
     * membership yet is enrolled on the spot rather than shown a dead end.
     */
    private function requireMember(Request $request): CommunityMember|RedirectResponse
    {
        $member = $this->currentMember($request);

        if ($member) {
            return $member;
        }

        $user = $request->user();

        $member = app(CommunityEnrollmentService::class)->record(
            email: $user->email,
            attributes: [
                ...CommunityEnrollmentService::splitName($user->name),
                'phone' => $user->phone,
                'user_id' => $user->id,
            ],
            source: CommunityMember::SOURCE_JOIN_FORM,
            notify: true,
        );

        return $member ?? redirect()->route('community.join');
    }

    /**
     * @param  array<int, int>  $trackIds
     * @return array<int, array<string, bool>>
     */
    private function trackSyncPayload(CommunityMember $member, array $trackIds): array
    {
        $currentPrimary = $member->tracks()->wherePivot('is_primary', true)->value('tac_tracks.id');
        $payload = [];

        foreach (array_values(array_unique($trackIds)) as $index => $trackId) {
            $payload[$trackId] = [
                'is_primary' => $currentPrimary
                    ? (int) $trackId === (int) $currentPrimary
                    : $index === 0,
            ];
        }

        if ($payload !== [] && ! collect($payload)->contains('is_primary', true)) {
            $payload[array_key_first($payload)]['is_primary'] = true;
        }

        return $payload;
    }
}
