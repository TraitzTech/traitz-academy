<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Community\Concerns\ResolvesCommunityMember;
use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacLeader;
use App\Models\TacPartner;
use App\Models\TacTrack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    use ResolvesCommunityMember;

    /**
     * The TAC home page. Everything on it is live data — recent joiners,
     * upcoming activities, real leaders — because a community page that looks
     * static reads as abandoned.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Community/Index', [
            'stats' => $this->publicStats(),
            'tracks' => TacTrack::query()
                ->active()
                ->ordered()
                ->withCount(['members', 'activities as upcoming_count' => fn ($q) => $q->upcoming()])
                ->with('mentors:id,name,photo_path,tac_track_id')
                ->get(),
            'upcoming' => $this->upcomingActivities(6),
            'recentHighlights' => TacActivity::query()
                ->published()
                ->past()
                ->whereNotNull('outcome_summary')
                ->with(['track:id,name,slug', 'media'])
                ->orderByDesc('starts_at')
                ->take(3)
                ->get(),
            'featuredLeaders' => TacLeader::query()
                ->active()
                ->where('is_featured', true)
                ->with('track:id,name')
                ->ordered()
                ->take(6)
                ->get(),
            'partners' => TacPartner::query()
                ->active()
                ->orderBy('sort_order')
                ->take(12)
                ->get(['id', 'name', 'slug', 'logo_path', 'website_url', 'tier']),
            'isMember' => $this->currentMember($request) !== null,
        ]);
    }

    public function about(Request $request): Response
    {
        return Inertia::render('Community/About', [
            'stats' => $this->publicStats(),
            'tracks' => TacTrack::query()->active()->ordered()->get(['id', 'name', 'slug', 'tagline', 'icon', 'accent_color']),
            'leadership' => TacLeader::query()
                ->active()
                ->whereIn('role_type', TacLeader::EXECUTIVE_ROLES)
                ->ordered()
                ->get(['id', 'name', 'slug', 'role_type', 'role_title', 'photo_path', 'bio']),
            'isMember' => $this->currentMember($request) !== null,
        ]);
    }

    /**
     * Contact / Get Involved — the routes into TAC beyond simply joining:
     * mentoring, hosting at a school, partnering, speaking.
     */
    public function getInvolved(Request $request): Response
    {
        return Inertia::render('Community/GetInvolved', [
            'tracks' => TacTrack::query()->active()->ordered()->get(['id', 'name', 'slug']),
            'contacts' => [
                'email' => \App\Helpers\SettingHelper::contactEmail(),
                'phone' => \App\Helpers\SettingHelper::contactPhone(),
                'whatsapp' => \App\Helpers\SettingHelper::whatsAppCommunityLink(),
            ],
            'partnershipLeads' => TacLeader::query()
                ->active()
                ->where('role_type', TacLeader::ROLE_PARTNERSHIP_LEAD)
                ->ordered()
                ->get(['id', 'name', 'email', 'photo_path']),
            'schoolLeads' => TacLeader::query()
                ->active()
                ->where('role_type', TacLeader::ROLE_SCHOOL_LEAD)
                ->ordered()
                ->get(['id', 'name', 'school', 'photo_path']),
            'isMember' => $this->currentMember($request) !== null,
        ]);
    }

    /**
     * Headline numbers shown across the public pages.
     *
     * @return array<string, int>
     */
    private function publicStats(): array
    {
        return [
            'members' => CommunityMember::query()->active()->count(),
            'tracks' => TacTrack::query()->active()->count(),
            'leaders' => TacLeader::query()->active()->count(),
            'activities' => TacActivity::query()->published()->count(),
            'upcoming' => TacActivity::query()->upcoming()->count(),
            'schools' => CommunityMember::query()
                ->whereNotNull('school')
                ->where('school', '!=', '')
                ->distinct('school')
                ->count('school'),
        ];
    }

    private function upcomingActivities(int $limit)
    {
        return TacActivity::query()
            ->upcoming()
            ->with(['track:id,name,slug,accent_color', 'organizer:id,name,photo_path'])
            ->orderBy('starts_at')
            ->take($limit)
            ->get();
    }
}
