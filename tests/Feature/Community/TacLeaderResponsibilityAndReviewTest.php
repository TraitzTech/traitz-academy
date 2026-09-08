<?php

use App\Models\TacLeader;
use App\Models\TacLeaderPerformanceReview;
use App\Models\TacLeaderResponsibility;
use App\Models\TacTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function respStaff(): User
{
    return User::factory()->create(['role' => User::ROLE_CTO]);
}

/** A TAC Lead who is a plain `user` account — executive within TAC, but not academy staff. */
function respTacLeadNonStaff(): User
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_LEAD,
        'tac_track_id' => null,
    ]);

    return $user;
}

function respTrackMentor(TacTrack $track): array
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    $leader = TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $track->id,
    ]);

    return [$user, $leader];
}

// --------------------------------------------------------- Responsibilities

it('lets academy staff assign a responsibility to a leader', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    $this->actingAs(respStaff())
        ->post("/admin/community/leaders/{$leader->id}/responsibilities", [
            'title' => 'Run a monthly meetup',
            'description' => 'At least one event per month for the track.',
            'due_date' => now()->addMonth()->toDateString(),
        ])
        ->assertRedirect();

    expect($leader->responsibilities()->where('title', 'Run a monthly meetup')->exists())->toBeTrue();
});

it('forbids a track mentor from assigning themselves a responsibility', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    $this->actingAs($mentorUser)
        ->post("/admin/community/leaders/{$leader->id}/responsibilities", [
            'title' => 'Self-assigned',
        ])
        ->assertForbidden();
});

it('forbids a non-staff TAC Lead from assigning responsibilities', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    $this->actingAs(respTacLeadNonStaff())
        ->post("/admin/community/leaders/{$leader->id}/responsibilities", [
            'title' => 'Assigned by a peer lead',
        ])
        ->assertForbidden();
});

it('lets a leader mark their own responsibility as in progress or completed', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    $responsibility = TacLeaderResponsibility::query()->create([
        'tac_leader_id' => $leader->id,
        'title' => 'Run a monthly meetup',
    ]);

    $this->actingAs($mentorUser)
        ->patch("/admin/community/leaders/{$leader->id}/responsibilities/{$responsibility->id}/status", [
            'status' => 'completed',
        ])
        ->assertRedirect();

    $responsibility->refresh();

    expect($responsibility->status)->toBe('completed')
        ->and($responsibility->completed_at)->not->toBeNull();
});

it('forbids a leader from updating someone else’s responsibility', function () {
    $track = TacTrack::query()->first();
    [$mentorUser] = respTrackMentor($track);
    [, $otherLeader] = respTrackMentor($track);

    $responsibility = TacLeaderResponsibility::query()->create([
        'tac_leader_id' => $otherLeader->id,
        'title' => 'Not yours',
    ]);

    $this->actingAs($mentorUser)
        ->patch("/admin/community/leaders/{$otherLeader->id}/responsibilities/{$responsibility->id}/status", [
            'status' => 'completed',
        ])
        ->assertForbidden();
});

it('does not let a leader edit the content of their own responsibility', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    $responsibility = TacLeaderResponsibility::query()->create([
        'tac_leader_id' => $leader->id,
        'title' => 'Original title',
    ]);

    $this->actingAs($mentorUser)
        ->post("/admin/community/leaders/{$leader->id}/responsibilities/{$responsibility->id}", [
            'title' => 'Rewritten by the leader themselves',
        ])
        ->assertForbidden();
});

it('shows a leader their own responsibilities on their dashboard', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    TacLeaderResponsibility::query()->create([
        'tac_leader_id' => $leader->id,
        'title' => 'Visible to me',
    ]);

    $this->actingAs($mentorUser)
        ->get('/admin/community')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Community/LeaderDashboard')
            ->where('leaderships.0.responsibilities.0.title', 'Visible to me'));
});

// -------------------------------------------------------- Performance

it('lets academy staff write a performance review', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    $this->actingAs(respStaff())
        ->post("/admin/community/leaders/{$leader->id}/reviews", [
            'rating' => 4,
            'period_label' => 'September 2026',
            'notes' => 'Great turnout at the last workshop.',
        ])
        ->assertRedirect();

    expect(TacLeaderPerformanceReview::query()->where('tac_leader_id', $leader->id)->count())->toBe(1);
});

it('forbids a track mentor from writing their own performance review', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    $this->actingAs($mentorUser)
        ->post("/admin/community/leaders/{$leader->id}/reviews", ['rating' => 5])
        ->assertForbidden();
});

it('forbids a non-staff TAC Lead from writing performance reviews', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    $this->actingAs(respTacLeadNonStaff())
        ->post("/admin/community/leaders/{$leader->id}/reviews", ['rating' => 5])
        ->assertForbidden();
});

it('shows a leader their own performance reviews on their dashboard', function () {
    [$mentorUser, $leader] = respTrackMentor(TacTrack::query()->first());

    TacLeaderPerformanceReview::query()->create([
        'tac_leader_id' => $leader->id,
        'rating' => 5,
        'reviewed_by' => respStaff()->id,
    ]);

    $this->actingAs($mentorUser)
        ->get('/admin/community')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Community/LeaderDashboard')
            ->where('leaderships.0.performance_reviews.0.rating', 5));
});

// -------------------------------------------------------------- Show page

it('lets staff view a leader profile page', function () {
    [, $leader] = respTrackMentor(TacTrack::query()->first());

    $this->actingAs(respStaff())
        ->get("/admin/community/leaders/{$leader->id}")
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Community/Leaders/Show'));
});

it('lets a non-staff TAC executive view (but not manage) a leader profile page', function () {
    [, $leader] = respTrackMentor(TacTrack::query()->first());

    $this->actingAs(respTacLeadNonStaff())
        ->get("/admin/community/leaders/{$leader->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Community/Leaders/Show')
            ->where('can.manageResponsibilities', false));
});

it('forbids a track mentor from viewing another leader’s profile page', function () {
    $track = TacTrack::query()->first();
    [$mentorUser] = respTrackMentor($track);
    [, $otherLeader] = respTrackMentor($track);

    $this->actingAs($mentorUser)
        ->get("/admin/community/leaders/{$otherLeader->id}")
        ->assertForbidden();
});
