<?php

namespace App\Http\Controllers\Community;

use App\Helpers\SettingHelper;
use App\Http\Controllers\Controller;
use App\Models\TacLeader;
use App\Models\TacPartner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $partners = TacPartner::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Grouped by tier in a fixed order so Platinum never renders below
        // Community just because of insertion order.
        $tiers = collect(TacPartner::TIER_ORDER)
            ->map(fn (string $tier) => [
                'tier' => $tier,
                'label' => TacPartner::TIER_LABELS[$tier],
                'partners' => $partners->where('tier', $tier)->values(),
            ])
            ->filter(fn (array $group) => $group['partners']->isNotEmpty())
            ->values();

        return Inertia::render('Community/Partners', [
            'tiers' => $tiers,
            'featured' => $partners->where('is_featured', true)->values(),
            'total' => $partners->count(),
            'partnershipLeads' => TacLeader::query()
                ->active()
                ->where('role_type', TacLeader::ROLE_PARTNERSHIP_LEAD)
                ->ordered()
                ->get(['id', 'name', 'email', 'photo_path', 'bio']),
            'contactEmail' => SettingHelper::contactEmail(),
        ]);
    }
}
