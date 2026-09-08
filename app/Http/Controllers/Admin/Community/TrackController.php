<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\TacTrack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TrackController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TacTrack::class);

        $tracks = TacTrack::query()
            ->withCount([
                'members',
                'activities',
                'mentors',
                'activities as upcoming_activities_count' => fn ($q) => $q->upcoming(),
            ])
            ->with('mentors:id,name,tac_track_id,photo_path')
            ->ordered()
            ->get();

        return Inertia::render('Admin/Community/Tracks/Index', [
            'tracks' => $tracks,
            'can' => [
                'create' => $request->user()->hasTacExecutiveAuthority(),
                'manageTrackIds' => $request->user()->hasTacExecutiveAuthority()
                    ? $tracks->pluck('id')
                    : $request->user()->tacManagedTrackIds(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TacTrack::class);

        $track = TacTrack::create($this->validated($request));

        return back()->with('success', "“{$track->name}” track created.");
    }

    public function update(Request $request, TacTrack $track): RedirectResponse
    {
        $this->authorize('update', $track);

        $track->update($this->validated($request, $track));

        return back()->with('success', "“{$track->name}” updated.");
    }

    public function destroy(TacTrack $track): RedirectResponse
    {
        $this->authorize('delete', $track);

        // Members and activities point at tracks; retiring keeps the history
        // readable, so we refuse a destructive delete when either exists.
        if ($track->members()->exists() || $track->activities()->exists()) {
            return back()->with('error', "“{$track->name}” has members or activities. Deactivate it instead of deleting.");
        }

        $name = $track->name;

        if ($track->cover_image) {
            Storage::disk('public')->delete($track->cover_image);
        }

        $track->delete();

        return back()->with('success', "“{$name}” deleted.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('create', TacTrack::class);

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:tac_tracks,id'],
        ]);

        foreach ($validated['order'] as $position => $id) {
            TacTrack::query()->whereKey($id)->update(['sort_order' => $position + 1]);
        }

        return back()->with('success', 'Track order updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TacTrack $track = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tac_tracks', 'slug')->ignore($track?->id)],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:64'],
            'accent_color' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
        ]);

        unset($validated['cover_image']);

        if ($request->hasFile('cover_image')) {
            if ($track?->cover_image) {
                Storage::disk('public')->delete($track->cover_image);
            }

            $validated['cover_image'] = $request->file('cover_image')->store('community/tracks', 'public');
        }

        return $validated;
    }
}
