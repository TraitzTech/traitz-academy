<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreTacActivityRequest;
use App\Models\Program;
use App\Models\TacActivity;
use App\Models\TacActivityRsvp;
use App\Models\TacLeader;
use App\Models\TacTrack;
use App\Models\User;
use App\Services\Tac\ActivityRecurrenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __construct(private ActivityRecurrenceService $recurrence) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TacActivity::class);

        $query = TacActivity::query()
            ->manageableBy($request->user())
            ->with(['track:id,name,slug', 'organizer:id,name'])
            ->withCount('rsvps')
            ->whereNull('parent_activity_id');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%"));
        }

        foreach (['type', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('track')) {
            $query->whereHas('track', fn ($q) => $q->where('slug', $request->input('track')));
        }

        match ($request->input('window')) {
            'upcoming' => $query->where('starts_at', '>=', now()),
            'past' => $query->where('starts_at', '<', now()),
            default => null,
        };

        return Inertia::render('Admin/Community/Activities/Index', [
            'activities' => $query->orderByDesc('starts_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'type', 'status', 'track', 'window']),
            'tracks' => TacTrack::query()->ordered()->get(['id', 'name', 'slug']),
            'types' => $this->typeOptions(),
            'stats' => $this->stats($request),
            'can' => ['publish' => $request->user()->hasTacExecutiveAuthority()],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', TacActivity::class);

        return Inertia::render('Admin/Community/Activities/Form', [
            'activity' => null,
            ...$this->formOptions($request),
        ]);
    }

    public function store(StoreTacActivityRequest $request): RedirectResponse
    {
        $this->authorize('create', TacActivity::class);

        $data = $this->prepare($request);
        $criteria = $request->input('criteria', []);

        $activity = DB::transaction(function () use ($data, $criteria, $request) {
            $activity = TacActivity::create([
                ...$data,
                'created_by' => $request->user()->id,
            ]);

            $this->syncCriteria($activity, $criteria);
            $this->recurrence->generate($activity);

            return $activity;
        });

        return redirect()
            ->route('admin.community.activities.show', $activity)
            ->with('success', "“{$activity->title}” created.");
    }

    public function show(Request $request, TacActivity $activity): Response
    {
        $this->authorize('view', $activity);

        $activity->load(['track:id,name,slug', 'organizer:id,name,role_type', 'program:id,title', 'media', 'competitionCriteria']);
        $activity->loadCount(['rsvps', 'competitionEntries', 'occurrences']);

        $rsvps = $activity->rsvps()
            ->with('member:id,first_name,last_name,email,phone,school')
            ->when($request->filled('rsvp_status'), fn ($q) => $q->where('status', $request->input('rsvp_status')))
            ->when($request->filled('rsvp_search'), function ($q) use ($request) {
                $term = $request->input('rsvp_search');
                $q->whereHas('member', fn ($m) => $m->search($term));
            })
            ->latest()
            ->paginate(25, ['*'], 'rsvps')
            ->withQueryString();

        return Inertia::render('Admin/Community/Activities/Show', [
            'activity' => $activity,
            'rsvps' => $rsvps,
            'filters' => $request->only(['rsvp_status', 'rsvp_search']),
            'rsvpStatuses' => collect(TacActivityRsvp::STATUS_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'breakdown' => $activity->rsvps()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'can' => [
                'update' => $request->user()->can('update', $activity),
                'publish' => $request->user()->can('publish', $activity),
                'judge' => $request->user()->can('judge', $activity),
            ],
        ]);
    }

    public function edit(Request $request, TacActivity $activity): Response
    {
        $this->authorize('update', $activity);

        $activity->load(['competitionCriteria', 'media']);

        return Inertia::render('Admin/Community/Activities/Form', [
            'activity' => $activity,
            ...$this->formOptions($request),
        ]);
    }

    public function update(StoreTacActivityRequest $request, TacActivity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $data = $this->prepare($request, $activity);

        // Only executives may move something onto (or off) the public calendar.
        if ($data['status'] !== $activity->status && ! $request->user()->can('publish', $activity)) {
            unset($data['status'], $data['published_at']);
        }

        DB::transaction(function () use ($activity, $data, $request) {
            $activity->update($data);
            $this->syncCriteria($activity, $request->input('criteria', []));
        });

        return back()->with('success', "“{$activity->title}” updated.");
    }

    public function destroy(TacActivity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        $title = $activity->title;

        if ($activity->cover_image) {
            Storage::disk('public')->delete($activity->cover_image);
        }

        $activity->delete();

        return redirect()
            ->route('admin.community.activities.index')
            ->with('success', "“{$title}” deleted.");
    }

    /**
     * Publish / unpublish / cancel from the list without opening the form.
     */
    public function setStatus(Request $request, TacActivity $activity): RedirectResponse
    {
        $this->authorize('publish', $activity);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,published,cancelled,completed'],
        ]);

        $activity->update([
            'status' => $validated['status'],
            'published_at' => $validated['status'] === TacActivity::STATUS_PUBLISHED
                ? ($activity->published_at ?? now())
                : $activity->published_at,
        ]);

        return back()->with('success', "“{$activity->title}” is now {$validated['status']}.");
    }

    public function toggleFeatured(TacActivity $activity): RedirectResponse
    {
        $this->authorize('publish', $activity);

        $activity->update(['is_featured' => ! $activity->is_featured]);

        return back()->with('success', $activity->is_featured
            ? "“{$activity->title}” is now featured."
            : "“{$activity->title}” is no longer featured.");
    }

    /**
     * @return array<string, mixed>
     */
    private function prepare(StoreTacActivityRequest $request, ?TacActivity $activity = null): array
    {
        $data = $request->safe()->except(['cover_image', 'criteria']);
        $user = $request->user();

        // Only executives and school leads organise across the whole
        // community; a track mentor (or anyone else) may only place an
        // activity in their own track, and may only name themselves as
        // organiser — never another leader or a different track.
        if (! $this->hasCrossTrackAccess($user)) {
            if (! empty($data['tac_track_id'])
                && ! in_array((int) $data['tac_track_id'], $user->tacManagedTrackIds(), true)) {
                abort(403, 'You may only create or edit activities in your own track.');
            }

            if (! empty($data['organizer_leader_id'])
                && ! in_array((int) $data['organizer_leader_id'], $user->tacLeaderIds(), true)) {
                abort(403, 'You may only set yourself as the organiser for this activity.');
            }
        }

        if ($request->hasFile('cover_image')) {
            if ($activity?->cover_image) {
                Storage::disk('public')->delete($activity->cover_image);
            }

            $data['cover_image'] = $request->file('cover_image')->store('community/activities', 'public');
        }

        $data['timezone'] = $data['timezone'] ?? config('app.timezone', 'Africa/Douala');
        $data['currency'] = $data['currency'] ?? 'XAF';

        if (empty($data['is_paid'])) {
            $data['price'] = 0;
        }

        if (($data['status'] ?? null) === TacActivity::STATUS_PUBLISHED) {
            $data['published_at'] = $activity?->published_at ?? now();
        }

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $criteria
     */
    private function syncCriteria(TacActivity $activity, array $criteria): void
    {
        if (! $activity->isCompetition()) {
            return;
        }

        $keptIds = [];

        foreach (array_values($criteria) as $index => $row) {
            $criterion = $activity->competitionCriteria()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'label' => $row['label'],
                    'description' => $row['description'] ?? null,
                    'max_score' => (int) $row['max_score'],
                    'weight' => (int) $row['weight'],
                    'sort_order' => $index,
                ],
            );

            $keptIds[] = $criterion->id;
        }

        // Dropping a criterion cascades its scores away, so entries are
        // rescored against the rubric that actually applies.
        $removed = $activity->competitionCriteria()->whereNotIn('id', $keptIds)->get();

        if ($removed->isNotEmpty()) {
            $activity->competitionCriteria()->whereIn('id', $removed->pluck('id'))->delete();
            $activity->competitionEntries()->get()->each->recalculateScore();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $user = $request->user();
        $crossTrackAccess = $this->hasCrossTrackAccess($user);

        return [
            'tracks' => TacTrack::query()
                ->ordered()
                ->when(! $crossTrackAccess, fn ($q) => $q->whereIn('id', $user->tacManagedTrackIds()))
                ->get(['id', 'name', 'slug']),
            'leaders' => TacLeader::query()
                ->active()
                ->ordered()
                ->when(! $crossTrackAccess, fn ($q) => $q->whereIn('id', $user->tacLeaderIds()))
                ->get(['id', 'name', 'role_type', 'tac_track_id']),
            'programs' => Program::query()->orderBy('title')->get(['id', 'title']),
            'types' => $this->typeOptions(),
            'canPublish' => $user->hasTacExecutiveAuthority(),
            'defaultTimezone' => config('app.timezone', 'Africa/Douala'),
        ];
    }

    /**
     * Executives organise across every track; a school lead also gets the
     * full list since a campus event may cover any track. Everyone else
     * (track mentors, partnership leads) is limited to their own track and
     * their own name as organiser.
     */
    private function hasCrossTrackAccess(User $user): bool
    {
        return $user->hasTacExecutiveAuthority() || $user->tacManagedSchools() !== [];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return collect(TacActivity::TYPE_LABELS)
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function stats(Request $request): array
    {
        $base = fn () => TacActivity::query()->manageableBy($request->user())->whereNull('parent_activity_id');

        return [
            'total' => $base()->count(),
            'published' => $base()->where('status', TacActivity::STATUS_PUBLISHED)->count(),
            'drafts' => $base()->where('status', TacActivity::STATUS_DRAFT)->count(),
            'upcoming' => $base()->upcoming()->count(),
            'rsvps' => (int) $base()->sum('rsvp_count'),
        ];
    }
}
