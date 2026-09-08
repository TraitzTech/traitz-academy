<?php

use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacLeader;
use App\Models\TacPartner;
use App\Models\TacTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

/**
 * Every TAC page must render with real data *and* while completely empty —
 * a community platform is at its emptiest on day one, which is exactly when
 * a blank-state crash would be most visible.
 */
beforeEach(function () {
    $this->track = TacTrack::query()->first();
});

function seedCommunity(): void
{
    $track = TacTrack::query()->first();

    TacLeader::factory()->create([
        'role_type' => TacLeader::ROLE_LEAD,
        'tac_track_id' => null,
        'is_featured' => true,
    ]);
    TacLeader::factory()->create([
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $track->id,
    ]);
    TacLeader::factory()->create([
        'role_type' => TacLeader::ROLE_SCHOOL_LEAD,
        'tac_track_id' => null,
        'school' => 'University of Buea',
    ]);
    TacLeader::factory()->retired()->create([
        'role_type' => TacLeader::ROLE_CO_LEAD,
        'tac_track_id' => null,
    ]);

    TacActivity::factory()->create(['tac_track_id' => $track->id]);
    TacActivity::factory()->past()->create([
        'tac_track_id' => $track->id,
        'status' => TacActivity::STATUS_COMPLETED,
        'outcome_summary' => 'It went well.',
    ]);
    TacActivity::factory()->competition()->create(['tac_track_id' => $track->id]);

    TacPartner::query()->create([
        'name' => 'Acme Corp',
        'slug' => 'acme-corp',
        'tier' => 'gold',
        'is_active' => true,
    ]);

    CommunityMember::factory()->inDirectory()->count(3)->create()
        ->each(fn ($member) => $member->tracks()->attach($track->id));
}

// ------------------------------------------------------------- Public pages

it('renders every public community page when there is nothing in it yet', function () {
    $pages = [
        ['/community', 'Community/Index'],
        ['/community/about', 'Community/About'],
        ['/community/tracks', 'Community/Tracks/Index'],
        ['/community/team', 'Community/Team'],
        ['/community/activities', 'Community/Activities/Index'],
        ['/community/partners', 'Community/Partners'],
        ['/community/get-involved', 'Community/GetInvolved'],
        ['/community/join', 'Community/Join'],
    ];

    foreach ($pages as [$url, $component]) {
        $this->get($url)
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }
});

it('renders every public community page with real content', function () {
    seedCommunity();

    foreach ([
        '/community',
        '/community/about',
        '/community/tracks',
        '/community/team',
        '/community/activities',
        '/community/activities?window=past',
        '/community/partners',
        '/community/get-involved',
        '/community/join',
    ] as $url) {
        $this->get($url)->assertSuccessful();
    }
});

it('renders a track page', function () {
    seedCommunity();

    $this->get("/community/tracks/{$this->track->slug}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Community/Tracks/Show'));
});

it('renders an activity page, including a competition', function () {
    seedCommunity();

    $activity = TacActivity::query()->where('type', TacActivity::TYPE_WORKSHOP)->first();
    $competition = TacActivity::query()->where('type', TacActivity::TYPE_COMPETITION)->first();

    $this->get("/community/activities/{$activity->slug}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Community/Activities/Show'));

    $this->get("/community/activities/{$competition->slug}")->assertSuccessful();
});

it('hides an inactive track from the public site', function () {
    $this->track->update(['is_active' => false]);

    $this->get("/community/tracks/{$this->track->slug}")->assertNotFound();
});

// -------------------------------------------------------------- Member area

it('requires an account for the member area', function () {
    foreach (['/community/me', '/community/me/profile', '/community/me/directory'] as $url) {
        $this->get($url)->assertRedirect('/login');
    }
});

it('renders the member area and enrols a signed-in user who is not yet a member', function () {
    seedCommunity();

    $user = User::factory()->create(['name' => 'Nkeng Ayuk']);

    $this->actingAs($user)
        ->get('/community/me')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page->component('Community/Member/Dashboard'));

    // Visiting the member area is itself a way into TAC.
    expect(CommunityMember::query()->where('user_id', $user->id)->exists())->toBeTrue();

    $this->actingAs($user)->get('/community/me/profile')->assertSuccessful();
    $this->actingAs($user)->get('/community/me/directory')->assertSuccessful();
});

it('lets a member update their profile and tracks', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/community/me');

    $member = CommunityMember::query()->where('user_id', $user->id)->first();
    $trackIds = TacTrack::query()->take(2)->pluck('id')->all();

    $this->actingAs($user)
        ->post('/community/me/profile', [
            'first_name' => 'Renamed',
            'current_status' => CommunityMember::STATUS_PAST_INTERN,
            'directory_opt_in' => true,
            'email_opt_in' => false,
            'track_ids' => $trackIds,
        ])
        ->assertRedirect();

    $member->refresh();

    expect($member->first_name)->toBe('Renamed')
        ->and($member->directory_opt_in)->toBeTrue()
        ->and($member->email_opt_in)->toBeFalse()
        ->and($member->tracks)->toHaveCount(2);
});

it('only shows opted-in members in the directory', function () {
    $listed = CommunityMember::factory()->inDirectory()->create(['first_name' => 'Listed']);
    CommunityMember::factory()->create(['first_name' => 'Hidden']);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/community/me/directory')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Community/Member/Directory')
            ->where('members.total', 1)
            ->where('members.data.0.full_name', $listed->full_name));
});

it('never exposes contact details through the directory', function () {
    CommunityMember::factory()->inDirectory()->create([
        'email' => 'private@example.com',
        'phone' => '+237600000000',
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->get('/community/me/directory');

    $response->assertDontSee('private@example.com')
        ->assertDontSee('+237600000000');
});

// -------------------------------------------------------------- Admin pages

it('renders every community admin page', function () {
    seedCommunity();

    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $activity = TacActivity::query()->where('type', TacActivity::TYPE_WORKSHOP)->first();
    $competition = TacActivity::query()->where('type', TacActivity::TYPE_COMPETITION)->first();
    $member = CommunityMember::query()->first();

    $pages = [
        ['/admin/community', 'Admin/Community/Dashboard'],
        ['/admin/community/members', 'Admin/Community/Members/Index'],
        ["/admin/community/members/{$member->id}", 'Admin/Community/Members/Show'],
        ['/admin/community/tracks', 'Admin/Community/Tracks/Index'],
        ['/admin/community/leaders', 'Admin/Community/Leaders/Index'],
        ['/admin/community/activities', 'Admin/Community/Activities/Index'],
        ['/admin/community/activities/create', 'Admin/Community/Activities/Form'],
        ["/admin/community/activities/{$activity->slug}", 'Admin/Community/Activities/Show'],
        ["/admin/community/activities/{$activity->slug}/edit", 'Admin/Community/Activities/Form'],
        ["/admin/community/activities/{$competition->slug}/judge", 'Admin/Community/Competitions/Judge'],
        ['/admin/community/partners', 'Admin/Community/Partners/Index'],
    ];

    foreach ($pages as [$url, $component]) {
        $this->actingAs($admin)
            ->get($url)
            ->assertSuccessful()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }
});

it('renders the admin pages on a completely empty community', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);

    foreach ([
        '/admin/community',
        '/admin/community/members',
        '/admin/community/tracks',
        '/admin/community/leaders',
        '/admin/community/activities',
        '/admin/community/partners',
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertSuccessful();
    }
});

it('exports the member roster as CSV', function () {
    seedCommunity();

    $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_CTO]))
        ->get('/admin/community/members/export');

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
