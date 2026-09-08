<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacTrack;
use App\Services\Tac\CommunityEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function __construct(private CommunityEnrollmentService $enrollment) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CommunityMember::class);

        $user = $request->user();

        $query = CommunityMember::query()
            ->visibleTo($user)
            ->with(['tracks:id,name,slug', 'user:id,name,email'])
            ->search($request->input('search'));

        if ($request->filled('track')) {
            $query->whereHas('tracks', fn ($q) => $q->where('tac_tracks.slug', $request->input('track')));
        }

        foreach (['source', 'membership_status', 'lifecycle_status', 'current_status', 'school'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('directory')) {
            $query->where('directory_opt_in', $request->input('directory') === 'yes');
        }

        if ($request->filled('engagement')) {
            match ($request->input('engagement')) {
                'engaged' => $query->where('engagement_score', '>', 0),
                'dormant' => $query->where('engagement_score', 0),
                default => null,
            };
        }

        $sort = in_array($request->input('sort'), ['name', 'joined', 'engagement'], true)
            ? $request->input('sort')
            : 'joined';

        match ($sort) {
            'name' => $query->orderBy('first_name')->orderBy('last_name'),
            'engagement' => $query->orderByDesc('engagement_score')->orderByDesc('last_engaged_at'),
            default => $query->orderByDesc('joined_at')->orderByDesc('id'),
        };

        return Inertia::render('Admin/Community/Members/Index', [
            'members' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only([
                'search', 'track', 'source', 'membership_status',
                'lifecycle_status', 'current_status', 'school', 'directory', 'engagement', 'sort',
            ]),
            'tracks' => TacTrack::query()->ordered()->get(['id', 'name', 'slug']),
            'schools' => CommunityMember::query()
                ->visibleTo($user)
                ->whereNotNull('school')
                ->where('school', '!=', '')
                ->distinct()
                ->orderBy('school')
                ->pluck('school'),
            'stats' => $this->stats($request),
            'options' => $this->options(),
            'can' => [
                'update' => $user->hasTacExecutiveAuthority(),
                'export' => $user->can('export', CommunityMember::class),
            ],
        ]);
    }

    public function show(Request $request, CommunityMember $member): Response
    {
        $this->authorize('view', $member);

        $member->load([
            'tracks:id,name,slug',
            'user:id,name,email,role',
            'leadership.track:id,name',
            'rsvps.activity:id,title,slug,type,starts_at',
            'competitionEntries.activity:id,title,slug',
        ]);

        return Inertia::render('Admin/Community/Members/Show', [
            'member' => $member,
            'sourceLabel' => $member->sourceLabel(),
            'tracks' => TacTrack::query()->ordered()->get(['id', 'name', 'slug']),
            'options' => $this->options(),
            'can' => [
                'update' => $request->user()->can('update', $member),
                'delete' => $request->user()->can('delete', $member),
            ],
        ]);
    }

    public function update(Request $request, CommunityMember $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('community_members', 'email')->ignore($member->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'school' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'current_status' => ['required', Rule::in(array_keys(CommunityMember::CURRENT_STATUS_LABELS))],
            'membership_status' => ['required', Rule::in(array_keys(CommunityMember::MEMBERSHIP_LABELS))],
            'lifecycle_status' => ['required', Rule::in([
                CommunityMember::LIFECYCLE_ACTIVE,
                CommunityMember::LIFECYCLE_DORMANT,
                CommunityMember::LIFECYCLE_UNSUBSCRIBED,
                CommunityMember::LIFECYCLE_BLOCKED,
            ])],
            'directory_opt_in' => ['boolean'],
            'email_opt_in' => ['boolean'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'track_ids' => ['array'],
            'track_ids.*' => ['integer', 'exists:tac_tracks,id'],
        ]);

        $trackIds = $validated['track_ids'] ?? null;
        unset($validated['track_ids']);

        $member->update($validated);

        if ($trackIds !== null) {
            $member->tracks()->sync($this->trackSyncPayload($member, $trackIds));
        }

        return back()->with('success', "{$member->full_name} updated.");
    }

    /**
     * Add somebody to the community by hand — for a lead met at a campus visit
     * or an event sign-up sheet that never made it through the site.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CommunityMember::class);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'school' => ['nullable', 'string', 'max:255'],
            'current_status' => ['nullable', Rule::in(array_keys(CommunityMember::CURRENT_STATUS_LABELS))],
            'track_ids' => ['array'],
            'track_ids.*' => ['integer', 'exists:tac_tracks,id'],
            'notify' => ['boolean'],
        ]);

        $existing = CommunityMember::query()->where('email', mb_strtolower($validated['email']))->exists();

        $member = $this->enrollment->record(
            email: $validated['email'],
            attributes: $validated,
            source: CommunityMember::SOURCE_ADMIN,
            trackIds: $validated['track_ids'] ?? [],
            notify: (bool) ($validated['notify'] ?? false),
        );

        if (! $member) {
            return back()->with('error', 'That email address is not valid.');
        }

        return back()->with('success', $existing
            ? "{$member->full_name} was already a member — their details were updated."
            : "{$member->full_name} added to the community.");
    }

    public function destroy(CommunityMember $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $name = $member->full_name;
        $member->delete();

        return back()->with('success', "{$name} removed from the community.");
    }

    /**
     * Segment operations: promote to mentor, mark dormant, unsubscribe, delete.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $this->authorize('create', CommunityMember::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:community_members,id'],
            'action' => ['required', Rule::in(['membership_status', 'lifecycle_status', 'add_track', 'delete'])],
            'value' => ['nullable', 'string', 'max:255'],
            'track_id' => ['nullable', 'integer', 'exists:tac_tracks,id'],
        ]);

        $members = CommunityMember::query()->whereIn('id', $validated['ids'])->get();
        $count = $members->count();

        switch ($validated['action']) {
            case 'membership_status':
                abort_unless(in_array($validated['value'], array_keys(CommunityMember::MEMBERSHIP_LABELS), true), 422);
                CommunityMember::query()->whereIn('id', $validated['ids'])
                    ->update(['membership_status' => $validated['value']]);
                $message = "{$count} member(s) set to ".CommunityMember::MEMBERSHIP_LABELS[$validated['value']].'.';
                break;

            case 'lifecycle_status':
                abort_unless(in_array($validated['value'], [
                    CommunityMember::LIFECYCLE_ACTIVE,
                    CommunityMember::LIFECYCLE_DORMANT,
                    CommunityMember::LIFECYCLE_UNSUBSCRIBED,
                    CommunityMember::LIFECYCLE_BLOCKED,
                ], true), 422);
                CommunityMember::query()->whereIn('id', $validated['ids'])
                    ->update(['lifecycle_status' => $validated['value']]);
                $message = "{$count} member(s) updated.";
                break;

            case 'add_track':
                abort_unless(filled($validated['track_id'] ?? null), 422);
                foreach ($members as $member) {
                    $this->enrollment->attachTracks($member, [(int) $validated['track_id']]);
                }
                $message = "Track added to {$count} member(s).";
                break;

            default:
                CommunityMember::query()->whereIn('id', $validated['ids'])->delete();
                $message = "{$count} member(s) removed.";
        }

        return back()->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', CommunityMember::class);

        $members = CommunityMember::query()
            ->visibleTo($request->user())
            ->with('tracks:id,name')
            ->search($request->input('search'))
            ->when($request->filled('track'), fn ($q) => $q->whereHas(
                'tracks',
                fn ($t) => $t->where('tac_tracks.slug', $request->input('track'))
            ))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->input('source')))
            ->when($request->filled('membership_status'), fn ($q) => $q->where('membership_status', $request->input('membership_status')))
            ->orderByDesc('joined_at')
            ->get();

        $filename = 'tac-members-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($members) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'First name', 'Last name', 'Email', 'Phone', 'School', 'Status',
                'Tracks', 'Membership', 'Lifecycle', 'Source', 'In directory',
                'Email opt-in', 'Engagement', 'Joined',
            ]);

            foreach ($members as $member) {
                fputcsv($handle, [
                    $member->first_name,
                    $member->last_name,
                    $member->email,
                    $member->phone,
                    $member->school,
                    $member->statusLabel(),
                    $member->tracks->pluck('name')->implode('; '),
                    CommunityMember::MEMBERSHIP_LABELS[$member->membership_status] ?? $member->membership_status,
                    $member->lifecycle_status,
                    $member->sourceLabel(),
                    $member->directory_opt_in ? 'Yes' : 'No',
                    $member->email_opt_in ? 'Yes' : 'No',
                    $member->engagement_score,
                    $member->joined_at?->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Keep exactly one primary track when an admin re-syncs the selection.
     *
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

        // The old primary may have been removed from the selection — promote
        // the first remaining track so a member never ends up with none.
        if ($payload !== [] && ! collect($payload)->contains('is_primary', true)) {
            $payload[array_key_first($payload)]['is_primary'] = true;
        }

        return $payload;
    }

    /**
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    private function options(): array
    {
        $map = fn (array $labels) => collect($labels)
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();

        return [
            'currentStatuses' => $map(CommunityMember::CURRENT_STATUS_LABELS),
            'membershipStatuses' => $map(CommunityMember::MEMBERSHIP_LABELS),
            'sources' => $map(CommunityMember::SOURCE_LABELS),
            'lifecycleStatuses' => $map([
                CommunityMember::LIFECYCLE_ACTIVE => 'Active',
                CommunityMember::LIFECYCLE_DORMANT => 'Dormant',
                CommunityMember::LIFECYCLE_UNSUBSCRIBED => 'Unsubscribed',
                CommunityMember::LIFECYCLE_BLOCKED => 'Blocked',
            ]),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function stats(Request $request): array
    {
        $base = fn () => CommunityMember::query()->visibleTo($request->user());

        return [
            'total' => $base()->count(),
            'active' => $base()->active()->count(),
            'joined_this_month' => $base()->where('joined_at', '>=', now()->startOfMonth())->count(),
            'auto_included' => $base()->where('source', '!=', CommunityMember::SOURCE_JOIN_FORM)->count(),
            'in_directory' => $base()->where('directory_opt_in', true)->count(),
            'mentors' => $base()->whereIn('membership_status', [
                CommunityMember::MEMBERSHIP_MENTOR,
                CommunityMember::MEMBERSHIP_LEAD,
            ])->count(),
        ];
    }
}
