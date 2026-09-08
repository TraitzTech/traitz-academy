<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Community\Concerns\ResolvesCommunityMember;
use App\Http\Controllers\Controller;
use App\Models\TacActivity;
use App\Models\TacCompetitionEntry;
use App\Models\TacTrack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    use ResolvesCommunityMember;

    /**
     * The ongoing calendar: upcoming by default, with the full past archive
     * reachable from the same view.
     */
    public function index(Request $request): Response
    {
        $window = in_array($request->input('window'), ['upcoming', 'past'], true)
            ? $request->input('window')
            : 'upcoming';

        $query = TacActivity::query()
            ->published()
            ->with(['track:id,name,slug,accent_color', 'organizer:id,name,photo_path'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('track'), fn ($q) => $q->whereHas('track', fn ($t) => $t->where('slug', $request->input('track'))))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');
                $q->where(fn ($inner) => $inner->where('title', 'like', "%{$term}%")
                    ->orWhere('summary', 'like', "%{$term}%"));
            });

        $query = $window === 'past'
            ? $query->past()->orderByDesc('starts_at')
            : $query->upcoming()->orderBy('starts_at');

        return Inertia::render('Community/Activities/Index', [
            'activities' => $query->paginate(12)->withQueryString(),
            'filters' => [...$request->only(['type', 'track', 'search']), 'window' => $window],
            'tracks' => TacTrack::query()->active()->ordered()->get(['id', 'name', 'slug', 'accent_color']),
            'types' => collect(TacActivity::TYPE_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'counts' => [
                'upcoming' => TacActivity::query()->upcoming()->count(),
                'past' => TacActivity::query()->past()->count(),
            ],
            'featured' => $window === 'upcoming'
                ? TacActivity::query()->upcoming()->where('is_featured', true)
                    ->with('track:id,name,slug,accent_color')
                    ->orderBy('starts_at')->first()
                : null,
        ]);
    }

    public function show(Request $request, TacActivity $activity): Response
    {
        abort_unless(
            in_array($activity->status, [TacActivity::STATUS_PUBLISHED, TacActivity::STATUS_COMPLETED, TacActivity::STATUS_CANCELLED], true),
            404,
        );

        $activity->load([
            'track:id,name,slug,tagline,accent_color',
            'organizer:id,name,role_type,photo_path,bio',
            'program:id,title,slug',
            'media',
            'competitionCriteria',
            'occurrences' => fn ($q) => $q->published()->orderBy('starts_at'),
        ]);

        $member = $this->currentMember($request);

        $myRsvp = $member
            ? $activity->rsvps()->where('community_member_id', $member->id)->first()
            : null;

        $myEntry = $member && $activity->isCompetition()
            ? $activity->competitionEntries()->where('community_member_id', $member->id)->first()
            : null;

        return Inertia::render('Community/Activities/Show', [
            'activity' => $activity,
            'registration' => $activity->registrationState(),
            'seatsLeft' => $activity->seatsLeft(),
            'myRsvp' => $myRsvp,
            'myEntry' => $myEntry,
            'isMember' => $member !== null,
            'leaderboard' => $this->leaderboard($activity),
            'related' => TacActivity::query()
                ->upcoming()
                ->where('id', '!=', $activity->id)
                ->when($activity->tac_track_id, fn ($q) => $q->where('tac_track_id', $activity->tac_track_id))
                ->with('track:id,name,slug,accent_color')
                ->orderBy('starts_at')
                ->take(3)
                ->get(),
        ]);
    }

    /**
     * Published results only. Nothing leaks while judging is in progress.
     */
    private function leaderboard(TacActivity $activity)
    {
        if (! $activity->isCompetition()) {
            return null;
        }

        return $activity->competitionEntries()
            ->publishedResults()
            ->where('status', '!=', TacCompetitionEntry::STATUS_DISQUALIFIED)
            ->with('member:id,first_name,last_name,school,avatar_path')
            ->orderBy('rank')
            ->get()
            ->map(fn (TacCompetitionEntry $entry) => [
                'id' => $entry->id,
                'rank' => $entry->rank,
                'title' => $entry->title,
                'team_name' => $entry->team_name,
                'repo_url' => $entry->repo_url,
                'demo_url' => $entry->demo_url,
                'total_score' => $entry->total_score,
                'is_winner' => $entry->is_winner,
                'award' => $entry->award,
                'member' => [
                    'name' => $entry->member?->full_name,
                    'school' => $entry->member?->school,
                    'avatar_path' => $entry->member?->avatar_path,
                ],
            ]);
    }
}
