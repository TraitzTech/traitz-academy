<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('shows active sessions on profile settings page', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'session-user-1',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) Chrome/145.0.0.0',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/Profile')
        ->has('sessions')
        ->where('sessions.0.id', 'session-user-1')
    );
});

it('allows users to terminate one of their sessions', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'session-user-terminate',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->actingAs($user)->delete(route('sessions.destroy', ['sessionId' => 'session-user-terminate']));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect(DB::table('sessions')->where('id', 'session-user-terminate')->exists())->toBeFalse();
});

it('does not allow users to terminate sessions that are not theirs', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'session-other-user',
        'user_id' => $otherUser->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $response = $this->actingAs($user)->delete(route('sessions.destroy', ['sessionId' => 'session-other-user']));

    $response->assertRedirect();
    $response->assertSessionHas('error');

    expect(DB::table('sessions')->where('id', 'session-other-user')->exists())->toBeTrue();
});

it('allows users to terminate all other sessions', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        [
            'id' => 'session-user-a',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'session-user-b',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ],
    ]);

    $response = $this->actingAs($user)->delete(route('sessions.destroy-other'));

    $response->assertRedirect();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBeLessThan(2);
});
