<?php

use App\Models\TacActivity;
use App\Models\TacLeader;
use App\Models\TacTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('auto-generates a unique slug when a leader is created', function () {
    $leader = TacLeader::factory()->create(['name' => 'Ada Lovelace']);

    expect($leader->slug)->toBe('ada-lovelace');
});

it('disambiguates leaders who share a name', function () {
    $first = TacLeader::factory()->create(['name' => 'Ada Lovelace']);
    $second = TacLeader::factory()->create(['name' => 'Ada Lovelace']);

    expect($first->slug)->toBe('ada-lovelace')
        ->and($second->slug)->toBe('ada-lovelace-2');
});

it('keeps a leader’s slug stable even after their name changes', function () {
    $leader = TacLeader::factory()->create(['name' => 'Ada Lovelace']);

    $leader->update(['name' => 'Ada King']);

    expect($leader->fresh()->slug)->toBe('ada-lovelace');
});

it('renders a leader’s public profile page', function () {
    $track = TacTrack::query()->first();

    $leader = TacLeader::factory()->create([
        'name' => 'Grace Hopper',
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $track->id,
        'bio' => 'Compiler pioneer.',
    ]);

    $this->get("/community/team/{$leader->slug}")
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Community/TeamMember')
            ->where('leader.name', 'Grace Hopper')
            ->where('leader.bio', 'Compiler pioneer.')
            ->where('isRetired', false));
});

it('flags a retired leader as retired on their profile page', function () {
    $leader = TacLeader::factory()->retired()->create(['name' => 'Franklin Oben']);

    $this->get("/community/team/{$leader->slug}")
        ->assertInertia(fn (Assert $page) => $page->where('isRetired', true));
});

it('lists only published activities the leader organises on their profile', function () {
    $leader = TacLeader::factory()->create();

    $published = TacActivity::factory()->create(['organizer_leader_id' => $leader->id]);
    TacActivity::factory()->draft()->create(['organizer_leader_id' => $leader->id]);
    TacActivity::factory()->create(); // someone else's activity

    $this->get("/community/team/{$leader->slug}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('activities', 1)
            ->where('activities.0.id', $published->id));
});

it('404s for an unknown leader slug', function () {
    $this->get('/community/team/nobody-here')->assertNotFound();
});
