<?php

use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacLeader;
use App\Models\TacTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function tacExecutive(): User
{
    return User::factory()->create(['role' => User::ROLE_CTO]);
}

function trackMentor(TacTrack $track): User
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $track->id,
    ]);

    return $user;
}

// --------------------------------------------------------------- Admin gate

it('keeps ordinary users out of the community admin area', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($user)->get(route('admin.community.dashboard'))->assertForbidden();
});

it('lets an academy executive in', function () {
    $this->actingAs(tacExecutive())->get(route('admin.community.dashboard'))->assertSuccessful();
});

it('lets a track mentor in even though they are a plain user account', function () {
    $track = TacTrack::query()->first();

    $this->actingAs(trackMentor($track))->get(route('admin.community.dashboard'))->assertSuccessful();
});

// ------------------------------------------------------------ Member access

it('shows a track mentor only members in their own track', function () {
    [$mine, $theirs] = TacTrack::query()->take(2)->get();

    $inMyTrack = CommunityMember::factory()->create();
    $inMyTrack->tracks()->attach($mine->id);

    $elsewhere = CommunityMember::factory()->create();
    $elsewhere->tracks()->attach($theirs->id);

    $mentor = trackMentor($mine);

    $this->actingAs($mentor)
        ->get(route('admin.community.members.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Community/Members/Index')
            ->where('members.total', 1)
            ->where('members.data.0.id', $inMyTrack->id));
});

it('shows an executive the whole roster', function () {
    CommunityMember::factory()->count(3)->create();

    $this->actingAs(tacExecutive())
        ->get(route('admin.community.members.index'))
        ->assertInertia(fn ($page) => $page->where('members.total', 3));
});

it('forbids a mentor from viewing a member outside their track', function () {
    [$mine, $theirs] = TacTrack::query()->take(2)->get();

    $elsewhere = CommunityMember::factory()->create();
    $elsewhere->tracks()->attach($theirs->id);

    $this->actingAs(trackMentor($mine))
        ->get(route('admin.community.members.show', $elsewhere))
        ->assertForbidden();
});

it('forbids a mentor from editing member records', function () {
    $track = TacTrack::query()->first();
    $member = CommunityMember::factory()->create();
    $member->tracks()->attach($track->id);

    $this->actingAs(trackMentor($track))
        ->put(route('admin.community.members.update', $member), [
            'first_name' => 'Hacked',
            'email' => $member->email,
            'current_status' => CommunityMember::STATUS_STUDENT,
            'membership_status' => CommunityMember::MEMBERSHIP_LEAD,
            'lifecycle_status' => CommunityMember::LIFECYCLE_ACTIVE,
        ])
        ->assertForbidden();

    expect($member->fresh()->first_name)->not->toBe('Hacked');
});

it('forbids a mentor from exporting the roster', function () {
    $track = TacTrack::query()->first();

    $this->actingAs(trackMentor($track))
        ->get(route('admin.community.members.export'))
        ->assertForbidden();
});

// ---------------------------------------------------------- Activity access

it('shows a track mentor only activities they can manage', function () {
    [$mine, $theirs] = TacTrack::query()->take(2)->get();

    TacActivity::factory()->create(['tac_track_id' => $mine->id]);
    TacActivity::factory()->create(['tac_track_id' => $theirs->id]);

    $this->actingAs(trackMentor($mine))
        ->get(route('admin.community.activities.index'))
        ->assertInertia(fn ($page) => $page->where('activities.total', 1));
});

it('lets a mentor edit an activity in their track but not publish it', function () {
    $track = TacTrack::query()->first();
    $activity = TacActivity::factory()->draft()->create(['tac_track_id' => $track->id]);
    $mentor = trackMentor($track);

    $this->actingAs($mentor)
        ->post(route('admin.community.activities.update', $activity), [
            'title' => 'Revised workshop',
            'type' => TacActivity::TYPE_WORKSHOP,
            'location_type' => 'physical',
            'location' => 'Buea',
            'status' => TacActivity::STATUS_PUBLISHED,
        ])
        ->assertRedirect();

    $activity->refresh();

    expect($activity->title)->toBe('Revised workshop')
        // The edit landed; the attempted self-publish did not.
        ->and($activity->status)->toBe(TacActivity::STATUS_DRAFT);
});

it('forbids a mentor from touching an activity in another track', function () {
    [$mine, $theirs] = TacTrack::query()->take(2)->get();
    $activity = TacActivity::factory()->create(['tac_track_id' => $theirs->id]);

    $this->actingAs(trackMentor($mine))
        ->delete(route('admin.community.activities.destroy', $activity))
        ->assertForbidden();
});

it('lets an executive publish an activity', function () {
    $activity = TacActivity::factory()->draft()->create();

    $this->actingAs(tacExecutive())
        ->post(route('admin.community.activities.status', $activity), ['status' => 'published'])
        ->assertRedirect();

    expect($activity->fresh()->status)->toBe(TacActivity::STATUS_PUBLISHED)
        ->and($activity->fresh()->published_at)->not->toBeNull();
});

// ------------------------------------------------------- Leadership roster

it('only lets executives appoint leaders', function () {
    $track = TacTrack::query()->first();

    $payload = [
        'name' => 'New Mentor',
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $track->id,
    ];

    $this->actingAs(trackMentor($track))
        ->post(route('admin.community.leaders.store'), $payload)
        ->assertForbidden();

    $this->actingAs(tacExecutive())
        ->post(route('admin.community.leaders.store'), $payload)
        ->assertRedirect();

    expect(TacLeader::query()->where('name', 'New Mentor')->exists())->toBeTrue();
});

it('requires a track when appointing a track mentor', function () {
    $this->actingAs(tacExecutive())
        ->post(route('admin.community.leaders.store'), [
            'name' => 'Trackless',
            'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        ])
        ->assertSessionHasErrors('tac_track_id');
});

it('retires a leader without deleting their record', function () {
    $leader = TacLeader::factory()->create();

    $this->actingAs(tacExecutive())
        ->post(route('admin.community.leaders.retire', $leader))
        ->assertRedirect();

    $leader->refresh();

    expect($leader->is_active)->toBeFalse()
        ->and($leader->ended_on)->not->toBeNull()
        ->and(TacLeader::query()->whereKey($leader->id)->exists())->toBeTrue();
});

it('drops a mentor’s admin access as soon as they are retired', function () {
    $track = TacTrack::query()->first();
    $mentor = trackMentor($track);

    $this->actingAs($mentor)->get(route('admin.community.dashboard'))->assertSuccessful();

    TacLeader::query()->where('user_id', $mentor->id)->update([
        'is_active' => false,
        'ended_on' => now()->toDateString(),
    ]);

    $this->actingAs($mentor->fresh())->get(route('admin.community.dashboard'))->assertForbidden();
});
