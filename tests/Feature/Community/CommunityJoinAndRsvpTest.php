<?php

use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacActivityRsvp;
use App\Models\TacTrack;
use App\Models\User;
use App\Notifications\Tac\TacJoinConfirmation;
use App\Notifications\Tac\TacRsvpConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

// ---------------------------------------------------------------- Join flow

it('lets anybody join the community from the public form', function () {
    $tracks = TacTrack::query()->take(2)->pluck('id')->all();

    $this->post(route('community.join.store'), [
        'first_name' => 'Ayuk',
        'last_name' => 'Tabe',
        'email' => 'ayuk@example.com',
        'phone' => '+237670000000',
        'school' => 'University of Buea',
        'current_status' => CommunityMember::STATUS_STUDENT,
        'heard_about' => 'A friend',
        'track_ids' => $tracks,
        'directory_opt_in' => true,
    ])->assertRedirect(route('community.welcome'));

    $member = CommunityMember::query()->where('email', 'ayuk@example.com')->first();

    expect($member)->not->toBeNull()
        ->and($member->source)->toBe(CommunityMember::SOURCE_JOIN_FORM)
        ->and($member->directory_opt_in)->toBeTrue()
        ->and($member->tracks)->toHaveCount(2);

    Notification::assertSentTo($member, TacJoinConfirmation::class);
});

it('requires at least one track when joining', function () {
    $this->post(route('community.join.store'), [
        'first_name' => 'No',
        'email' => 'notrack@example.com',
        'phone' => '+237670000001',
        'current_status' => CommunityMember::STATUS_STUDENT,
        'track_ids' => [],
    ])->assertSessionHasErrors('track_ids');

    expect(CommunityMember::count())->toBe(0);
});

it('updates rather than duplicates when an auto-included person joins explicitly', function () {
    $member = CommunityMember::factory()->create([
        'email' => 'known@example.com',
        'source' => CommunityMember::SOURCE_EVENT,
        'directory_opt_in' => false,
    ]);

    $this->post(route('community.join.store'), [
        'first_name' => 'Known',
        'email' => 'known@example.com',
        'phone' => '+237670000002',
        'current_status' => CommunityMember::STATUS_PAST_INTERN,
        'track_ids' => TacTrack::query()->take(1)->pluck('id')->all(),
        'directory_opt_in' => true,
    ])->assertRedirect(route('community.join'));

    expect(CommunityMember::query()->where('email', 'known@example.com')->count())->toBe(1)
        ->and($member->fresh()->directory_opt_in)->toBeTrue()
        ->and($member->fresh()->source)->toBe(CommunityMember::SOURCE_EVENT);
});

it('shows the welcome page only to the person who just joined', function () {
    // No session key and no account: nothing to show, so bounce to the form.
    $this->get(route('community.welcome'))->assertRedirect(route('community.join'));
});

// ---------------------------------------------------------------- RSVP flow

it('lets a guest rsvp and joins them to the community at the same time', function () {
    $activity = TacActivity::factory()->create();

    $this->post(route('community.activities.rsvp', $activity), [
        'first_name' => 'Bih',
        'last_name' => 'Ngwa',
        'email' => 'bih@example.com',
        'phone' => '+237670000003',
    ])->assertRedirect();

    $member = CommunityMember::query()->where('email', 'bih@example.com')->first();

    expect($member)->not->toBeNull()
        ->and($activity->fresh()->rsvp_count)->toBe(1)
        ->and($activity->rsvps()->first()->status)->toBe(TacActivityRsvp::STATUS_REGISTERED);

    Notification::assertSentTo($member, TacRsvpConfirmation::class);
});

it('waitlists once an activity is full', function () {
    $activity = TacActivity::factory()->withCapacity(1)->create();

    $this->post(route('community.activities.rsvp', $activity), [
        'first_name' => 'First',
        'email' => 'first@example.com',
    ])->assertRedirect();

    $this->post(route('community.activities.rsvp', $activity), [
        'first_name' => 'Second',
        'email' => 'second@example.com',
    ])->assertRedirect();

    $second = CommunityMember::query()->where('email', 'second@example.com')->first();

    expect($activity->rsvps()->where('community_member_id', $second->id)->first()->status)
        ->toBe(TacActivityRsvp::STATUS_WAITLISTED);
});

it('promotes the next waitlisted member when somebody cancels', function () {
    $activity = TacActivity::factory()->withCapacity(1)->create();

    $holder = User::factory()->create(['email' => 'holder@example.com']);
    $waiter = User::factory()->create(['email' => 'waiter@example.com']);

    $this->actingAs($holder)->post(route('community.activities.rsvp', $activity))->assertRedirect();
    $this->actingAs($waiter)->post(route('community.activities.rsvp', $activity))->assertRedirect();

    $waiterMember = CommunityMember::query()->where('email', 'waiter@example.com')->first();

    expect($activity->rsvps()->where('community_member_id', $waiterMember->id)->first()->status)
        ->toBe(TacActivityRsvp::STATUS_WAITLISTED);

    $this->actingAs($holder)->delete(route('community.activities.rsvp.cancel', $activity));

    expect($activity->rsvps()->where('community_member_id', $waiterMember->id)->first()->status)
        ->toBe(TacActivityRsvp::STATUS_REGISTERED);
});

it('refuses an rsvp once registration has closed', function () {
    $activity = TacActivity::factory()->create([
        'registration_closes_at' => now()->subDay(),
    ]);

    $this->post(route('community.activities.rsvp', $activity), [
        'first_name' => 'Late',
        'email' => 'late@example.com',
    ])->assertSessionHas('error');

    expect($activity->rsvps()->count())->toBe(0);
});

it('does not let the same person rsvp twice', function () {
    $activity = TacActivity::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('community.activities.rsvp', $activity));
    $this->actingAs($user)->post(route('community.activities.rsvp', $activity))->assertSessionHas('info');

    expect($activity->rsvps()->count())->toBe(1);
});

it('sends a paid activity to checkout instead of confirming it outright', function () {
    $activity = TacActivity::factory()->paid(5000)->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('community.activities.rsvp', $activity))
        ->assertRedirect(route('community.activities.checkout', $activity));

    expect($activity->rsvps()->first()->payment_status)->toBe(TacActivityRsvp::PAYMENT_PENDING);
});

it('hides draft activities from the public calendar', function () {
    $draft = TacActivity::factory()->draft()->create();
    $live = TacActivity::factory()->create();

    $this->get(route('community.activities.show', $draft))->assertNotFound();
    $this->get(route('community.activities.show', $live))->assertSuccessful();
});
