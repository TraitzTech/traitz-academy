<?php

namespace App\Http\Controllers\Admin\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityMember;
use App\Models\TacLeader;
use App\Models\TacLeaderResponsibility;
use App\Models\TacTrack;
use App\Models\User;
use App\Notifications\Tac\TacLeadershipWelcomeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LeaderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TacLeader::class);

        $leaders = TacLeader::query()
            ->with(['track:id,name,slug', 'user:id,name,email', 'member:id,first_name,last_name,email'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');
                $q->where(fn ($inner) => $inner->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('school', 'like', "%{$term}%"));
            })
            ->when($request->filled('role_type'), fn ($q) => $q->where('role_type', $request->input('role_type')))
            ->when($request->filled('track'), fn ($q) => $q->whereHas('track', fn ($t) => $t->where('slug', $request->input('track'))))
            ->when($request->input('state') === 'retired', fn ($q) => $q->retired())
            ->when($request->input('state') !== 'retired', fn ($q) => $q->active())
            ->ordered()
            ->get();

        return Inertia::render('Admin/Community/Leaders/Index', [
            'leaders' => $leaders,
            'filters' => $request->only(['search', 'role_type', 'track', 'state']),
            'tracks' => TacTrack::query()->ordered()->get(['id', 'name', 'slug']),
            'roleTypes' => collect(TacLeader::ROLE_LABELS)
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'schools' => TacLeader::query()->whereNotNull('school')->distinct()->orderBy('school')->pluck('school'),
            'assignableUsers' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'counts' => TacLeader::query()->active()
                ->selectRaw('role_type, count(*) as total')
                ->groupBy('role_type')
                ->pluck('total', 'role_type'),
            'can' => ['manage' => $request->user()->hasTacExecutiveAuthority()],
        ]);
    }

    /**
     * A single leader's profile for staff: responsibilities assigned to
     * them and their performance review history — the "how are they doing"
     * view the roster list doesn't have room for.
     */
    public function show(Request $request, TacLeader $leader): Response
    {
        $this->authorize('viewAny', TacLeaderResponsibility::class);

        $leader->load([
            'track:id,name,slug',
            'user:id,name,email',
            'responsibilities' => fn ($q) => $q->ordered()->with('assignedBy:id,name'),
            'performanceReviews' => fn ($q) => $q->recent()->with('reviewedBy:id,name'),
        ]);

        return Inertia::render('Admin/Community/Leaders/Show', [
            'leader' => $leader,
            'can' => [
                'manageResponsibilities' => $request->user()->canAccessAdminPanel(),
                'manageReviews' => $request->user()->canAccessAdminPanel(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TacLeader::class);

        $leader = TacLeader::create($this->validated($request));

        $loginMessage = $request->boolean('send_login')
            ? ' '.$this->provisionLogin($leader)
            : '';

        return back()->with('success', "{$leader->name} appointed as {$leader->roleLabel()}.{$loginMessage}");
    }

    public function update(Request $request, TacLeader $leader): RedirectResponse
    {
        $this->authorize('update', $leader);

        $leader->update($this->validated($request, $leader));

        $loginMessage = $request->boolean('send_login')
            ? ' '.$this->provisionLogin($leader)
            : '';

        return back()->with('success', "{$leader->name} updated.{$loginMessage}");
    }

    /**
     * Retire a leader rather than deleting them: leadership rotates, and the
     * public "past leaders" timeline depends on the record surviving.
     */
    public function retire(Request $request, TacLeader $leader): RedirectResponse
    {
        $this->authorize('update', $leader);

        $leader->update([
            'is_active' => false,
            'ended_on' => $request->date('ended_on') ?? now()->toDateString(),
        ]);

        return back()->with('success', "{$leader->name} retired from {$leader->roleLabel()}.");
    }

    public function reinstate(TacLeader $leader): RedirectResponse
    {
        $this->authorize('update', $leader);

        $leader->update(['is_active' => true, 'ended_on' => null]);

        return back()->with('success', "{$leader->name} reinstated.");
    }

    /**
     * Give a leader who has no account yet a real login. Callable directly
     * from the roster for someone already appointed, or opted into from the
     * appoint/edit form itself via `send_login` — either way it lands here.
     */
    public function createLogin(Request $request, TacLeader $leader): RedirectResponse
    {
        $this->authorize('update', $leader);

        return back()->with('success', $this->provisionLogin($leader));
    }

    /**
     * Create (or link to) a login for this leader and welcome them to their
     * TAC role by email — a warm welcome and a plain-language description of
     * what the role means always go out; login credentials are folded into
     * the same email, only when a brand new account is created just now.
     * Returns a message describing what happened so callers can fold it
     * into their own flash.
     */
    private function provisionLogin(TacLeader $leader): string
    {
        if ($leader->user_id) {
            return "{$leader->name} already has a login.";
        }

        if (blank($leader->email)) {
            return "Add an email address for {$leader->name} before creating a login.";
        }

        $existing = User::query()->where('email', $leader->email)->first();

        if ($existing) {
            $leader->update(['user_id' => $existing->id]);
            $existing->notify(new TacLeadershipWelcomeNotification($leader));

            return "{$leader->name} linked to their existing account and welcomed to their new TAC role.";
        }

        $plainPassword = (string) Str::password(12);

        $user = User::create([
            'name' => $leader->name,
            'email' => $leader->email,
            'phone' => $leader->phone,
            'role' => User::ROLE_USER,
            'password' => Hash::make($plainPassword),
        ]);

        $user->notify(new TacLeadershipWelcomeNotification($leader, $plainPassword));

        $leader->update(['user_id' => $user->id]);

        return "Login created for {$leader->name} — a welcome email with their credentials has been sent to {$leader->email}.";
    }

    public function destroy(TacLeader $leader): RedirectResponse
    {
        $this->authorize('delete', $leader);

        $name = $leader->name;

        if ($leader->photo_path) {
            Storage::disk('public')->delete($leader->photo_path);
        }

        $leader->delete();

        return back()->with('success', "{$name} removed from the leadership roster.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?TacLeader $leader = null): array
    {
        // Drop blank rows from the dynamic social-links editor before
        // validating, so an empty label or URL left in the form never
        // becomes a required field or a "must be a URL" error.
        $socialLinks = collect($request->input('social_links', []))
            ->filter(fn ($url, $label) => filled($url) && filled($label))
            ->all();
        $request->merge(['social_links' => $socialLinks]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role_type' => ['required', Rule::in(array_keys(TacLeader::ROLE_LABELS))],
            'role_title' => ['nullable', 'string', 'max:255'],
            'tac_track_id' => [
                'nullable', 'integer', 'exists:tac_tracks,id',
                Rule::requiredIf(fn () => $request->input('role_type') === TacLeader::ROLE_TRACK_MENTOR),
            ],
            'school' => [
                'nullable', 'string', 'max:255',
                Rule::requiredIf(fn () => $request->input('role_type') === TacLeader::ROLE_SCHOOL_LEAD),
            ],
            'bio' => ['nullable', 'string', 'max:2000'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            // Any platform, any label — a leader may list LinkedIn, X,
            // Instagram, a personal site, whatever they actually use. The
            // key is the display label; the value is the link.
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'max:255', 'url'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'community_member_id' => ['nullable', 'integer', 'exists:community_members,id'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'max:4096'],
            // Read separately via $request->boolean() after this validates,
            // so it's declared here for documentation but stripped below —
            // it controls provisioning, not a TacLeader column.
            'send_login' => ['boolean'],
        ], [
            'tac_track_id.required' => 'A track mentor must be assigned to a track.',
            'school.required' => 'A school lead must be assigned to a school.',
        ]);

        unset($validated['photo'], $validated['send_login']);

        if ($request->hasFile('photo')) {
            if ($leader?->photo_path) {
                Storage::disk('public')->delete($leader->photo_path);
            }

            $validated['photo_path'] = $request->file('photo')->store('community/leaders', 'public');
        }

        // Only a track mentor / school lead carries a track or school, so clear
        // stale values when somebody is moved between roles.
        if ($validated['role_type'] !== TacLeader::ROLE_TRACK_MENTOR) {
            $validated['tac_track_id'] = null;
        }

        if ($validated['role_type'] !== TacLeader::ROLE_SCHOOL_LEAD) {
            $validated['school'] = null;
        }

        $validated['started_on'] = $validated['started_on'] ?? ($leader?->started_on ?? now()->toDateString());

        // Appointing a leader promotes their community member record, so the
        // roster and the member list never disagree about who is a mentor.
        $this->syncMembership($validated);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncMembership(array $validated): void
    {
        $memberId = $validated['community_member_id'] ?? null;

        if (! $memberId) {
            return;
        }

        $member = CommunityMember::find($memberId);

        if (! $member) {
            return;
        }

        $member->update([
            'membership_status' => in_array($validated['role_type'], TacLeader::EXECUTIVE_ROLES, true)
                ? CommunityMember::MEMBERSHIP_LEAD
                : CommunityMember::MEMBERSHIP_MENTOR,
        ]);
    }
}
