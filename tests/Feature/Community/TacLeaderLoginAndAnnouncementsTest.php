<?php

use App\Models\CommunityMember;
use App\Models\TacLeader;
use App\Models\TacTrack;
use App\Models\User;
use App\Notifications\Tac\TacAnnouncementNotification;
use App\Notifications\Tac\TacLeadershipWelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function tacExec(): User
{
    return User::factory()->create(['role' => User::ROLE_CTO]);
}

function tacMentor(TacTrack $track): User
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);

    TacLeader::factory()->create([
        'user_id' => $user->id,
        'role_type' => TacLeader::ROLE_TRACK_MENTOR,
        'tac_track_id' => $track->id,
    ]);

    return $user;
}

// ------------------------------------------------------------- Create login

it('creates a login and emails credentials for a leader with no account', function () {
    Notification::fake();

    $leader = TacLeader::factory()->create(['email' => 'newlead@example.com', 'user_id' => null]);

    $this->actingAs(tacExec())
        ->post("/admin/community/leaders/{$leader->id}/create-login")
        ->assertRedirect();

    $leader->refresh();
    $user = User::query()->where('email', 'newlead@example.com')->first();

    expect($leader->user_id)->not->toBeNull()
        ->and($user)->not->toBeNull()
        ->and($leader->user_id)->toBe($user->id)
        ->and($user->role)->toBe(User::ROLE_USER);

    Notification::assertSentTo($user, TacLeadershipWelcomeNotification::class, function ($notification) use ($user) {
        $mail = $notification->toMail($user);
        $text = implode(' ', $mail->introLines);

        // Not just bare credentials — a warm welcome to the role, with the
        // login details folded into the same email.
        return str_contains($mail->greeting, 'Congratulations')
            && str_contains($text, 'Temporary password')
            && str_contains($text, 'newlead@example.com');
    });
});

it('links to an existing account by email instead of duplicating it, and still welcomes them', function () {
    Notification::fake();

    $existing = User::factory()->create(['email' => 'already@example.com']);
    $leader = TacLeader::factory()->create(['email' => 'already@example.com', 'user_id' => null]);

    $this->actingAs(tacExec())
        ->post("/admin/community/leaders/{$leader->id}/create-login")
        ->assertRedirect();

    expect($leader->fresh()->user_id)->toBe($existing->id)
        ->and(User::query()->where('email', 'already@example.com')->count())->toBe(1);

    // Linking to an existing account skips the credentials, not the welcome.
    Notification::assertSentTo($existing, TacLeadershipWelcomeNotification::class, function ($notification) use ($existing) {
        $mail = $notification->toMail($existing);
        $text = implode(' ', $mail->introLines);

        return str_contains($mail->greeting, 'Congratulations')
            && ! str_contains($text, 'Temporary password');
    });
});

it('refuses to create a login without an email on file', function () {
    $leader = TacLeader::factory()->create(['email' => null, 'user_id' => null]);

    $this->actingAs(tacExec())
        ->post("/admin/community/leaders/{$leader->id}/create-login")
        ->assertRedirect();

    expect($leader->fresh()->user_id)->toBeNull();
});

it('forbids a track mentor from creating logins for other leaders', function () {
    $track = TacTrack::query()->first();
    $mentor = tacMentor($track);
    $otherLeader = TacLeader::factory()->create(['email' => 'x@example.com', 'user_id' => null]);

    $this->actingAs($mentor)
        ->post("/admin/community/leaders/{$otherLeader->id}/create-login")
        ->assertForbidden();

    expect($otherLeader->fresh()->user_id)->toBeNull();
});

// ------------------------------------------------------------- Announcements

it('lets a track mentor announce to their own track only', function () {
    Notification::fake();

    [$mine, $theirs] = TacTrack::query()->take(2)->get();
    $mentor = tacMentor($mine);

    $inTrack = CommunityMember::factory()->create();
    $inTrack->tracks()->attach($mine->id);

    $elsewhere = CommunityMember::factory()->create();
    $elsewhere->tracks()->attach($theirs->id);

    $this->actingAs($mentor)
        ->post('/admin/community/announcements', [
            'audience' => 'my_track',
            'subject' => 'Workshop tomorrow',
            'message' => '<p>Don\'t forget to bring a laptop.</p>',
        ])
        ->assertRedirect();

    Notification::assertSentTo($inTrack, TacAnnouncementNotification::class);
    Notification::assertNotSentTo($elsewhere, TacAnnouncementNotification::class);
});

it('forbids a track mentor from announcing to the whole community', function () {
    $track = TacTrack::query()->first();
    $mentor = tacMentor($track);

    $this->actingAs($mentor)
        ->post('/admin/community/announcements', [
            'audience' => 'all_members',
            'subject' => 'Everyone',
            'message' => '<p>Hi</p>',
        ])
        ->assertForbidden();
});

it('lets an executive announce to every mailable member', function () {
    Notification::fake();

    $reachable = CommunityMember::factory()->create();
    $optedOut = CommunityMember::factory()->optedOut()->create();

    $this->actingAs(tacExec())
        ->post('/admin/community/announcements', [
            'audience' => 'all_members',
            'subject' => 'Community update',
            'message' => '<p>Big news.</p>',
        ])
        ->assertRedirect();

    Notification::assertSentTo($reachable, TacAnnouncementNotification::class);
    Notification::assertNotSentTo($optedOut, TacAnnouncementNotification::class);
});

it('reports nothing sent when the chosen audience has no members', function () {
    $track = TacTrack::query()->first();
    $mentor = tacMentor($track);

    $this->actingAs($mentor)
        ->post('/admin/community/announcements', [
            'audience' => 'my_track',
            'subject' => 'Hello',
            'message' => '<p>Hi</p>',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

// -------------------------------------------------- Recipient count display

it('gives a track mentor a numeric member_count for their own track, not undefined', function () {
    $track = TacTrack::query()->first();
    $mentor = tacMentor($track);

    CommunityMember::factory()->create()->tracks()->attach($track->id);

    $this->actingAs($mentor)
        ->get('/admin/community/announcements')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Community/Announcements/Index')
            ->where('myTracks.0.member_count', 1));
});

it('gives an executive a numeric member_count for every track in the full list', function () {
    CommunityMember::factory()->create()->tracks()->attach(TacTrack::query()->first()->id);

    $this->actingAs(tacExec())
        ->get('/admin/community/announcements')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Community/Announcements/Index')
            ->where('allTracks.0.member_count', fn ($count) => is_int($count)));
});
