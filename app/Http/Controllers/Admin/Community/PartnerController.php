<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\TacLeader;
use App\Models\TacPartner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TacPartner::class);

        return Inertia::render('Admin/Community/Partners/Index', [
            'partners' => TacPartner::query()
                ->with('partnershipLead:id,name')
                ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
                ->when($request->filled('tier'), fn ($q) => $q->where('tier', $request->input('tier')))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'filters' => $request->only(['search', 'tier']),
            'tiers' => collect(TacPartner::TIER_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'partnershipLeads' => TacLeader::query()
                ->active()
                ->where('role_type', TacLeader::ROLE_PARTNERSHIP_LEAD)
                ->ordered()
                ->get(['id', 'name']),
            'can' => [
                'manage' => $request->user()->can('create', TacPartner::class),
                'delete' => $request->user()->hasTacExecutiveAuthority(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TacPartner::class);

        $partner = TacPartner::create($this->validated($request));

        return back()->with('success', "{$partner->name} added as a partner.");
    }

    public function update(Request $request, TacPartner $partner): RedirectResponse
    {
        $this->authorize('update', $partner);

        $partner->update($this->validated($request, $partner));

        return back()->with('success', "{$partner->name} updated.");
    }

    public function destroy(TacPartner $partner): RedirectResponse
    {
        $this->authorize('delete', $partner);

        $name = $partner->name;

        if ($partner->logo_path) {
            Storage::disk('public')->delete($partner->logo_path);
        }

        $partner->delete();

        return back()->with('success', "{$name} removed.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TacPartner $partner = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tac_partners', 'slug')->ignore($partner?->id)],
            'website_url' => ['nullable', 'url', 'max:255'],
            'tier' => ['required', Rule::in(array_keys(TacPartner::TIER_LABELS))],
            'description' => ['nullable', 'string', 'max:2000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'partnership_lead_id' => ['nullable', 'integer', 'exists:tac_leaders,id'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);

        unset($validated['logo']);

        if ($request->hasFile('logo')) {
            if ($partner?->logo_path) {
                Storage::disk('public')->delete($partner->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('community/partners', 'public');
        }

        return $validated;
    }
}
