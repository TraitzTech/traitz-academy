<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\TacActivity;
use App\Models\TacLeader;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A leader's full public profile — the Team page only has room for a name,
 * photo and one-line role; this is where the whole bio, every social link,
 * and what they organise actually lives.
 */
class LeaderProfileController extends Controller
{
    public function show(Request $request, TacLeader $leader): Response
    {
        $leader->load('track:id,name,slug,accent_color');

        return Inertia::render('Community/TeamMember', [
            'leader' => $leader,
            'isRetired' => ! $leader->is_active || $leader->ended_on !== null,
            'activities' => TacActivity::query()
                ->where('organizer_leader_id', $leader->id)
                ->published()
                ->with('track:id,name,slug,accent_color')
                ->orderByDesc('starts_at')
                ->take(6)
                ->get(['id', 'title', 'slug', 'type', 'starts_at', 'ends_at', 'location', 'location_type', 'tac_track_id', 'cover_image', 'status']),
        ]);
    }
}
