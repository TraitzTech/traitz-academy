<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacLeader;
use App\Models\TacPartner;
use App\Models\TacTrack;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Executives get the org-wide view; everyone else who leads TAC gets a
     * dashboard scoped to exactly what they run — their track, their school,
     * or their partner portfolio. A track mentor should never land on a
     * screen shaped like it belongs to the CEO.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return $user->hasTacExecutiveAuthority()
            ? $this->executiveView($user)
            : $this->leaderView($user);
    }

    private function executiveView(User $user): Response
    {
        $members = fn () => CommunityMember::query()->visibleTo($user);

        return Inertia::render('Admin/Community/Dashboard', [
            'stats' => [
                'members' => $members()->count(),
                'members_this_month' => $members()->where('joined_at', '>=', now()->startOfMonth())->count(),
                'members_last_month' => $members()
                    ->whereBetween('joined_at', [
                        now()->subMonthNoOverflow()->startOfMonth(),
                        now()->subMonthNoOverflow()->endOfMonth(),
                    ])->count(),
                'auto_included' => $members()->where('source', '!=', CommunityMember::SOURCE_JOIN_FORM)->count(),
                'in_directory' => $members()->where('directory_opt_in', true)->count(),
                'leaders' => TacLeader::query()->active()->count(),
                'tracks' => TacTrack::query()->active()->count(),
                'partners' => TacPartner::query()->active()->count(),
                'upcoming_activities' => TacActivity::query()->manageableBy($user)->upcoming()->count(),
                'draft_activities' => TacActivity::query()->manageableBy($user)->where('status', TacActivity::STATUS_DRAFT)->count(),
                'total_rsvps' => (int) TacActivity::query()->manageableBy($user)->sum('rsvp_count'),
            ],

            // Twelve months of joins, so the "growing indefinitely" claim is
            // something an admin can actually see rather than take on faith.
            'growth' => $this->growth($user),

            'bySource' => $members()
                ->selectRaw('source, count(*) as total')
                ->groupBy('source')
                ->pluck('total', 'source')
                ->map(fn ($total, $source) => $total)
                ->all(),

            'sourceLabels' => CommunityMember::SOURCE_LABELS,

            'byTrack' => TacTrack::query()
                ->active()
                ->ordered()
                ->withCount(['members', 'activities as upcoming_count' => fn ($q) => $q->upcoming()])
                ->get(['id', 'name', 'slug', 'accent_color']),

            'recentMembers' => $members()
                ->with('tracks:id,name')
                ->latest('joined_at')
                ->take(8)
                ->get(['id', 'first_name', 'last_name', 'email', 'school', 'source', 'joined_at']),

            'upcoming' => TacActivity::query()
                ->manageableBy($user)
                ->upcoming()
                ->with('track:id,name')
                ->orderBy('starts_at')
                ->take(6)
                ->get(['id', 'title', 'slug', 'type', 'starts_at', 'location', 'location_type', 'tac_track_id', 'rsvp_count', 'capacity']),

            'needsAttention' => [
                'drafts' => TacActivity::query()->manageableBy($user)
                    ->where('status', TacActivity::STATUS_DRAFT)
                    ->orderByDesc('updated_at')
                    ->take(5)
                    ->get(['id', 'title', 'slug', 'type', 'updated_at']),
                'unscored_competitions' => TacActivity::query()->manageableBy($user)
                    ->where('type', TacActivity::TYPE_COMPETITION)
                    ->whereHas('competitionEntries', fn ($q) => $q->whereNull('total_score'))
                    ->take(5)
                    ->get(['id', 'title', 'slug']),
                'vacant_roles' => $this->vacantRoles(),
            ],

            'can' => [
                'executive' => true,
                'manageLeaders' => true,
                'managePartners' => true,
            ],
        ]);
    }

    /**
     * A track mentor, school lead or partnership lead only ever sees what
     * they themselves run — never the org-wide roster or another track's
     * numbers.
     */
    private function leaderView(User $user): Response
    {
        $leaderships = $user->activeTacLeaderships()->load([
            'track:id,name,slug,accent_color',
            'responsibilities' => fn ($q) => $q->ordered(),
            'performanceReviews' => fn ($q) => $q->recent()->with('reviewedBy:id,name'),
        ]);

        $trackIds = $user->tacManagedTrackIds();
        $schools = $user->tacManagedSchools();
        $isPartnershipLead = $user->isTacPartnershipLead();

        $tracks = $trackIds
            ? TacTrack::query()
                ->whereIn('id', $trackIds)
                ->withCount(['members', 'activities as upcoming_count' => fn ($q) => $q->upcoming()])
                ->get(['id', 'name', 'slug', 'accent_color'])
            : collect();

        $schoolStats = collect($schools)->map(fn (string $school) => [
            'name' => $school,
            'member_count' => CommunityMember::query()->where('school', $school)->count(),
        ])->values();

        $partners = $isPartnershipLead
            ? TacPartner::query()->whereIn('partnership_lead_id', $user->tacLeaderIds())->get(['id', 'name', 'slug', 'tier', 'is_active'])
            : collect();

        $members = CommunityMember::query()->visibleTo($user);
        $activities = TacActivity::query()->manageableBy($user);

        return Inertia::render('Admin/Community/LeaderDashboard', [
            'leaderships' => $leaderships->map(fn (TacLeader $l) => [
                'id' => $l->id,
                'role_type' => $l->role_type,
                'label' => $l->roleLabel(),
                'track' => $l->track?->only(['id', 'name', 'slug']),
                'school' => $l->school,
                'responsibilities' => $l->responsibilities,
                'performance_reviews' => $l->performanceReviews,
            ])->values(),

            'tracks' => $tracks,
            'schools' => $schoolStats,
            'partners' => $partners,

            'stats' => [
                'members' => (clone $members)->count(),
                'members_this_month' => (clone $members)->where('joined_at', '>=', now()->startOfMonth())->count(),
                'upcoming_activities' => (clone $activities)->upcoming()->count(),
                'draft_activities' => (clone $activities)->where('status', TacActivity::STATUS_DRAFT)->count(),
            ],

            'recentMembers' => (clone $members)
                ->with('tracks:id,name')
                ->latest('joined_at')
                ->take(6)
                ->get(['id', 'first_name', 'last_name', 'email', 'school', 'source', 'joined_at']),

            'upcoming' => (clone $activities)
                ->upcoming()
                ->with('track:id,name')
                ->orderBy('starts_at')
                ->take(6)
                ->get(['id', 'title', 'slug', 'type', 'starts_at', 'location', 'location_type', 'tac_track_id', 'rsvp_count', 'capacity']),

            'drafts' => (clone $activities)
                ->where('status', TacActivity::STATUS_DRAFT)
                ->orderByDesc('updated_at')
                ->take(5)
                ->get(['id', 'title', 'slug', 'type', 'updated_at']),
        ]);
    }

    /**
     * @return array<int, array{month: string, label: string, total: int}>
     */
    private function growth(User $user): array
    {
        $start = now()->subMonths(11)->startOfMonth();

        $counts = CommunityMember::query()
            ->visibleTo($user)
            ->where('joined_at', '>=', $start)
            ->get(['joined_at'])
            ->groupBy(fn ($m) => $m->joined_at?->format('Y-m'))
            ->map->count();

        $series = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');

            $series[] = [
                'month' => $key,
                'label' => $month->format('M'),
                'total' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Leadership posts with nobody currently in them — the spec expects
     * rotation, so an empty seat should be visible rather than silent.
     *
     * @return array<int, string>
     */
    private function vacantRoles(): array
    {
        $filled = TacLeader::query()->active()->pluck('role_type')->unique()->all();

        // Per-track mentors and school leads are many-to-one by nature; only
        // the singular posts are meaningfully "vacant".
        $singular = [
            TacLeader::ROLE_LEAD,
            TacLeader::ROLE_CO_LEAD,
            TacLeader::ROLE_SECRETARY,
        ];

        $vacant = array_values(array_diff($singular, $filled));

        $trackless = TacTrack::query()
            ->active()
            ->whereDoesntHave('mentors')
            ->pluck('name')
            ->map(fn ($name) => "{$name} mentor")
            ->all();

        return [
            ...array_map(fn ($role) => TacLeader::ROLE_LABELS[$role], $vacant),
            ...$trackless,
        ];
    }
}
