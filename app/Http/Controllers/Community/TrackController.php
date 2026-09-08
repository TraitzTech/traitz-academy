<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Community\Concerns\ResolvesCommunityMember;
use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacTrack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackController extends Controller
{
    use ResolvesCommunityMember;

    public function index(Request $request): Response
    {
        return Inertia::render('Community/Tracks/Index', [
            'tracks' => TacTrack::query()
                ->active()
                ->ordered()
                ->withCount([
                    'members',
                    'activities as upcoming_count' => fn ($q) => $q->upcoming(),
                ])
                ->with('mentors:id,name,slug,photo_path,tac_track_id,role_title')
                ->get(),
            'isMember' => $this->currentMember($request) !== null,
        ]);
    }

    public function show(Request $request, TacTrack $track): Response
    {
        abort_unless($track->is_active, 404);

        $track->loadCount(['members', 'activities']);
        $track->load('mentors:id,name,slug,photo_path,bio,role_title,tac_track_id,social_links,email');

        $member = $this->currentMember($request);

        return Inertia::render('Community/Tracks/Show', [
            'track' => $track,
            'upcoming' => TacActivity::query()
                ->upcoming()
                ->where('tac_track_id', $track->id)
                ->with('organizer:id,name,photo_path')
                ->orderBy('starts_at')
                ->take(6)
                ->get(),
            'past' => TacActivity::query()
                ->past()
                ->where('tac_track_id', $track->id)
                ->orderByDesc('starts_at')
                ->take(4)
                ->get(['id', 'title', 'slug', 'type', 'starts_at', 'cover_image', 'outcome_summary']),

            // The directory is opt-in only, so this shows the members who chose
            // to be findable — never the full roster.
            'members' => CommunityMember::query()
                ->inDirectory()
                ->whereHas('tracks', fn ($q) => $q->where('tac_tracks.id', $track->id))
                ->inRandomOrder()
                ->take(12)
                ->get(['id', 'first_name', 'last_name', 'school', 'avatar_path', 'membership_status']),
            'directoryCount' => CommunityMember::query()
                ->inDirectory()
                ->whereHas('tracks', fn ($q) => $q->where('tac_tracks.id', $track->id))
                ->count(),

            'isMember' => $member !== null,
            'inThisTrack' => $member
                ? $member->tracks()->where('tac_tracks.id', $track->id)->exists()
                : false,
        ]);
    }
}
