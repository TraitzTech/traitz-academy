<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\TacLeader;
use App\Models\TacTrack;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    /**
     * The leadership page, grouped by role type — all School Leads together,
     * all Mentors grouped under their track — plus the alumni-leaders timeline
     * that makes the community's history visible.
     */
    public function __invoke(Request $request): Response
    {
        $active = TacLeader::query()
            ->active()
            ->with('track:id,name,slug,accent_color')
            ->ordered()
            ->get();

        $groups = collect(TacLeader::ROLE_LABELS)
            ->map(fn (string $label, string $role) => [
                'role' => $role,
                'label' => $label,
                'plural' => $this->plural($label),
                'leaders' => $active->where('role_type', $role)->values(),
            ])
            ->filter(fn (array $group) => $group['leaders']->isNotEmpty())
            ->values();

        // Every track shows up here, mentored or not — a vacant seat is
        // useful information (see Get Involved), not something to hide.
        $mentorsByTrack = TacTrack::query()
            ->active()
            ->ordered()
            ->with(['mentors' => fn ($q) => $q->ordered()])
            ->get()
            ->map(fn (TacTrack $track) => [
                'track' => $track->name,
                'slug' => $track->slug,
                'accent_color' => $track->accent_color,
                'leaders' => $track->mentors->values(),
            ])
            ->values();

        $schoolLeads = $active
            ->where('role_type', TacLeader::ROLE_SCHOOL_LEAD)
            ->groupBy(fn (TacLeader $leader) => $leader->school ?? 'Unassigned')
            ->map(fn ($leaders, $school) => ['school' => $school, 'leaders' => $leaders->values()])
            ->values();

        return Inertia::render('Community/Team', [
            'groups' => $groups,
            'mentorsByTrack' => $mentorsByTrack,
            'schoolLeads' => $schoolLeads,
            'alumniLeaders' => TacLeader::query()
                ->retired()
                ->with('track:id,name')
                ->orderByDesc('ended_on')
                ->take(24)
                ->get(['id', 'slug', 'name', 'photo_path', 'role_type', 'role_title', 'tac_track_id', 'school', 'started_on', 'ended_on']),
            'roleLabels' => TacLeader::ROLE_LABELS,
            'counts' => [
                'active' => $active->count(),
                'mentors' => $active->where('role_type', TacLeader::ROLE_TRACK_MENTOR)->count(),
                'schools' => $active->where('role_type', TacLeader::ROLE_SCHOOL_LEAD)
                    ->pluck('school')->filter()->unique()->count(),
            ],
        ]);
    }

    private function plural(string $label): string
    {
        return match ($label) {
            'Secretary' => 'Secretaries',
            default => $label.'s',
        };
    }
}
