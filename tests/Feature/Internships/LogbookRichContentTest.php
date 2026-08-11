<?php

use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('sanitizes html content before storing a logbook entry', function () {
    $user = User::factory()->create();
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $this->actingAs($user)
        ->post('/dashboard/internship/logbook', [
            'content' => '<p>Worked on <strong>the API</strong>.</p><script>alert("x")</script><img src="https://example.com/shot.png">',
        ])
        ->assertSessionHasNoErrors();

    $entry = LogbookEntry::query()->latest('id')->first();

    expect($entry->content)
        ->toContain('<strong>the API</strong>')
        ->toContain('<img')
        ->not->toContain('<script>');
});

it('rejects a logbook entry that sanitizes down to nothing', function () {
    $user = User::factory()->create();
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $this->actingAs($user)
        ->post('/dashboard/internship/logbook', [
            'content' => '<script>alert(1)</script>',
        ])
        ->assertStatus(422);
});

it('lets an intern upload a screenshot for their logbook entry', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $response = $this->actingAs($user)
        ->post('/dashboard/internship/logbook/media', [
            'media' => UploadedFile::fake()->image('screenshot.png'),
        ]);

    $response->assertOk()->assertJsonStructure(['url', 'path']);
    Storage::disk('public')->assertExists($response->json('path'));
});
