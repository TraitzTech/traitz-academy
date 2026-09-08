<?php

use App\Models\CommunityMember;
use App\Models\TacLeader;
use App\Models\TacPartner;
use App\Models\TacTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function scopeExec(): User
{
    return User::factory()->create(['role' => User::ROLE_CTO]);
}

function scopeMentor(TacTrack $track): User
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $track->id,
    ]);

    return $user;
}

function scopeSchoolLead(string $school): User
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_SCHOOL_LEAD,
        'tac_track_id' => null,
        'school' => $school,
    ]);

    return $user;
}

function scopePartnershipLead(): User
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_PARTNERSHIP_LEAD,
        'tac_track_id' => null,
    ]);

    return $user;
}

// ------------------------------------------------------------------- Home

it('sends a track mentor with no other staff role to the community dashboard on login', function () {
    $track = TacTrack::query()->first();
    $mentor = scopeMentor($track);

    $this->actingAs($mentor)
        ->get('/dashboard')
        ->assertRedirect(route('admin.community.dashboard'));
});

it('leaves the generic dashboard alone for a plain student', function () {
    $student = User::factory()->create(['role' => User::ROLE_USER]);

    $this->actingAs($student)->get('/dashboard')->assertSuccessful();
});

// -------------------------------------------------------------- Dashboard

it('gives executives the org-wide dashboard component', function () {
    $this->actingAs(scopeExec())
        ->get('/admin/community')
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Community/Dashboard'));
});

it('gives a track mentor a scoped dashboard containing only their track', function () {
    [$mine, $theirs] = TacTrack::query()->take(2)->get();
    $mentor = scopeMentor($mine);

    CommunityMember::factory()->create()->tracks()->attach($mine->id);
    CommunityMember::factory()->create()->tracks()->attach($theirs->id);

    $this->actingAs($mentor)
        ->get('/admin/community')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Community/LeaderDashboard')
            ->where('tracks.0.id', $mine->id)
            ->where('stats.members', 1)
            ->missing('needsAttention'));
});

it('gives a school lead a scoped dashboard keyed on their school', function () {
    $lead = scopeSchoolLead('NAHPI');

    CommunityMember::factory()->create(['school' => 'NAHPI']);
    CommunityMember::factory()->create(['school' => 'Somewhere Else']);

    $this->actingAs($lead)
        ->get('/admin/community')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Community/LeaderDashboard')
            ->where('schools.0.name', 'NAHPI')
            ->where('schools.0.member_count', 1)
            ->where('stats.members', 1));
});

it('gives a partnership lead their own partner portfolio', function () {
    $lead = scopePartnershipLead();
    $leaderId = TacLeader::query()->where('user_id', $lead->id)->value('id');

    TacPartner::query()->create(['name' => 'Mine Inc', 'slug' => 'mine-inc', 'partnership_lead_id' => $leaderId]);
    TacPartner::query()->create(['name' => 'Not Mine Inc', 'slug' => 'not-mine-inc']);

    $this->actingAs($lead)
        ->get('/admin/community')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Community/LeaderDashboard')
            ->where('partners.0.name', 'Mine Inc')
            ->has('partners', 1));
});

// ------------------------------------------------------------- Leadership

it('forbids a track mentor from browsing the full leadership roster', function () {
    $track = TacTrack::query()->first();

    $this->actingAs(scopeMentor($track))
        ->get('/admin/community/leaders')
        ->assertForbidden();
});

it('still lets executives browse the full leadership roster', function () {
    $this->actingAs(scopeExec())
        ->get('/admin/community/leaders')
        ->assertSuccessful();
});

// ---------------------------------------------------------------- Partners

it('forbids a track mentor from viewing the partner directory', function () {
    $track = TacTrack::query()->first();

    $this->actingAs(scopeMentor($track))
        ->get('/admin/community/partners')
        ->assertForbidden();
});

it('lets a partnership lead view the partner directory', function () {
    $this->actingAs(scopePartnershipLead())
        ->get('/admin/community/partners')
        ->assertSuccessful();
});
