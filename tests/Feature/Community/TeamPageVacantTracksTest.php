<?php

use App\Models\TacLeader;
use App\Models\TacTrack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('lists every active track on the team page, mentored or not', function () {
    $mentored = TacTrack::query()->first();
    $totalTracks = TacTrack::query()->active()->count();

    TacLeader::factory()->create([
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $mentored->id,
    ]);

    $this->get('/community/team')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Community/Team')
            ->has('mentorsByTrack', $totalTracks)
            ->where(
                'mentorsByTrack.0.leaders',
                fn ($leaders) => count($leaders) === 1,
            ));
});
