<?php

use App\Models\TacActivity;
use App\Models\TacLeader;
use App\Models\TacTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function trackScopeExec(): User
{
    return User::factory()->create(['role' => User::ROLE_CTO]);
}

function trackScopeMentor(TacTrack $track): User
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $track->id,
    ]);

    return $user;
}

function trackScopeSchoolLead(): User
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_SCHOOL_LEAD,
        'tac_track_id' => null,
        'school' => 'NAHPI',
    ]);

    return $user;
}

it('only offers a track mentor their own track when creating an activity', function () {
    [$mine, $theirs] = TacTrack::query()->take(2)->get();
    $mentor = trackScopeMentor($mine);

    $this->actingAs($mentor)
        ->get('/admin/community/activities/create')
        ->assertInertia(fn (Assert $page) => $page
            ->has('tracks', 1)
            ->where('tracks.0.id', $mine->id));
});

it('offers a school lead every track when creating an activity', function () {
    $lead = trackScopeSchoolLead();
    $total = TacTrack::query()->count();

    $this->actingAs($lead)
        ->get('/admin/community/activities/create')
        ->assertInertia(fn (Assert $page) => $page->has('tracks', $total));
});

it('offers an executive every track when creating an activity', function () {
    $total = TacTrack::query()->count();

    $this->actingAs(trackScopeExec())
        ->get('/admin/community/activities/create')
        ->assertInertia(fn (Assert $page) => $page->has('tracks', $total));
});

it('refuses a track mentor who posts a track outside their own', function () {
    [$mine, $theirs] = TacTrack::query()->take(2)->get();
    $mentor = trackScopeMentor($mine);

    $this->actingAs($mentor)
        ->post('/admin/community/activities', [
            'title' => 'Sneaky workshop',
            'type' => 'workshop',
            'tac_track_id' => $theirs->id,
            'location_type' => 'physical',
            'location' => 'Buea',
            'status' => 'draft',
        ])
        ->assertForbidden();

    expect(TacActivity::query()->where('title', 'Sneaky workshop')->exists())->toBeFalse();
});

it('lets a track mentor create an activity in their own track', function () {
    $track = TacTrack::query()->first();
    $mentor = trackScopeMentor($track);

    $this->actingAs($mentor)
        ->post('/admin/community/activities', [
            'title' => 'My own workshop',
            'type' => 'workshop',
            'tac_track_id' => $track->id,
            'location_type' => 'physical',
            'location' => 'Buea',
            'status' => 'draft',
        ])
        ->assertRedirect();

    expect(TacActivity::query()->where('title', 'My own workshop')->first()?->tac_track_id)->toBe($track->id);
});

it('refuses a track mentor who assigns a different leader as organiser', function () {
    $track = TacTrack::query()->first();
    $mentor = trackScopeMentor($track);
    $otherLeader = TacLeader::factory()->create();

    $this->actingAs($mentor)
        ->post('/admin/community/activities', [
            'title' => 'Someone else runs this',
            'type' => 'workshop',
            'tac_track_id' => $track->id,
            'organizer_leader_id' => $otherLeader->id,
            'location_type' => 'physical',
            'location' => 'Buea',
            'status' => 'draft',
        ])
        ->assertForbidden();
});

it('still lets an executive create an activity in any track for any organiser', function () {
    [$track] = TacTrack::query()->take(1)->get();
    $otherLeader = TacLeader::factory()->create();

    $this->actingAs(trackScopeExec())
        ->post('/admin/community/activities', [
            'title' => 'Exec-run workshop',
            'type' => 'workshop',
            'tac_track_id' => $track->id,
            'organizer_leader_id' => $otherLeader->id,
            'location_type' => 'physical',
            'location' => 'Buea',
            'status' => 'draft',
        ])
        ->assertRedirect();

    expect(TacActivity::query()->where('title', 'Exec-run workshop')->exists())->toBeTrue();
});
