<?php

use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\User;

it('shows the logbook page with todays entry and paginated history', function () {
    $user = User::factory()->create();
    $internship = Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    LogbookEntry::factory()->submitted()->create([
        'internship_id' => $internship->id,
        'date' => now()->subDay()->toDateString(),
        'content' => 'Worked on yesterday\'s task.',
    ]);
    LogbookEntry::factory()->submitted()->create([
        'internship_id' => $internship->id,
        'date' => now()->toDateString(),
        'content' => 'Today\'s work.',
    ]);

    $this->actingAs($user)
        ->get('/dashboard/internship/logbook')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Internships/Logbook')
            ->where('todayEntry.content', 'Today\'s work.')
            ->where('entries.total', 1)
        );
});
