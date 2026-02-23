<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

// ---- Admin Event Registrations ----

it('allows admin to view event registrations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    EventRegistration::factory()->count(3)->create(['event_id' => $event->id]);

    $this->actingAs($admin)
        ->get(route('admin.events.registrations', $event))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Events/Registrations')
            ->has('event')
            ->has('registrations.data', 3)
        );
});

it('allows admin to filter registrations by search', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    EventRegistration::factory()->create([
        'event_id' => $event->id,
        'first_name' => 'UniqueSearchName',
    ]);
    EventRegistration::factory()->create([
        'event_id' => $event->id,
        'first_name' => 'SomeoneElse',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.events.registrations', [$event, 'search' => 'UniqueSearchName']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('registrations.data', 1)
        );
});

it('allows admin to update registration status', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    $registration = EventRegistration::factory()->create([
        'event_id' => $event->id,
        'status' => 'confirmed',
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.events.registrations.update', [$event, $registration]), [
            'status' => 'attended',
        ])
        ->assertRedirect();

    $registration->refresh();
    expect($registration->status)->toBe('attended');
    expect($registration->attended_at)->not->toBeNull();
});

it('prevents non-admin from viewing event registrations', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $event = Event::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.events.registrations', $event))
        ->assertForbidden();
});

// ---- Send Reminder ----

it('allows admin to send event reminders to all registrants', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create([
        'event_date' => now()->addDays(3),
    ]);
    $registrations = EventRegistration::factory()->count(3)->create([
        'event_id' => $event->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.events.send-reminder', $event))
        ->assertRedirect()
        ->assertSessionHas('success');

    Notification::assertCount(3);
});

it('returns error when sending reminder to event with no registrations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.events.send-reminder', $event))
        ->assertRedirect()
        ->assertSessionHas('error');
});

// ---- Already Registered Indicator ----

it('shows user registered event ids on events index', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['is_active' => true]);
    EventRegistration::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->has('userRegisteredEventIds', 1)
            ->where('userRegisteredEventIds.0', $event->id)
        );
});

it('shows empty registered event ids for guests', function () {
    Event::factory()->create(['is_active' => true]);

    $this->get(route('events.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->has('userRegisteredEventIds', 0)
        );
});

it('shows is_registered flag on event show page for registered user', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['is_active' => true]);
    EventRegistration::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    $this->actingAs($user)
        ->get(route('events.show', $event->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('isRegistered', true)
        );
});

it('shows is_registered as false for unregistered user on event show page', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('events.show', $event->slug))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Events/Show')
            ->where('isRegistered', false)
        );
});

// ---- Bulk Actions ----

it('allows admin to bulk update registration statuses', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    $registrations = EventRegistration::factory()->count(3)->create([
        'event_id' => $event->id,
        'status' => 'confirmed',
    ]);

    $ids = $registrations->pluck('id')->toArray();

    $this->actingAs($admin)
        ->post(route('admin.events.registrations.bulk-update', $event), [
            'ids' => $ids,
            'status' => 'attended',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    foreach ($registrations as $registration) {
        $registration->refresh();
        expect($registration->status)->toBe('attended');
        expect($registration->attended_at)->not->toBeNull();
    }
});

it('only updates registrations belonging to the event during bulk update', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    $ownRegistration = EventRegistration::factory()->create([
        'event_id' => $event->id,
        'status' => 'confirmed',
    ]);
    $otherRegistration = EventRegistration::factory()->create([
        'event_id' => $otherEvent->id,
        'status' => 'confirmed',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.events.registrations.bulk-update', $event), [
            'ids' => [$ownRegistration->id, $otherRegistration->id],
            'status' => 'attended',
        ])
        ->assertRedirect();

    $ownRegistration->refresh();
    $otherRegistration->refresh();
    expect($ownRegistration->status)->toBe('attended');
    expect($otherRegistration->status)->toBe('confirmed');
});

it('allows admin to bulk delete registrations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    $registrations = EventRegistration::factory()->count(3)->create([
        'event_id' => $event->id,
    ]);

    $ids = $registrations->pluck('id')->toArray();

    $this->actingAs($admin)
        ->post(route('admin.events.registrations.bulk-destroy', $event), [
            'ids' => $ids,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(EventRegistration::whereIn('id', $ids)->count())->toBe(0);
});

it('prevents non-admin from performing bulk actions on registrations', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $event = Event::factory()->create();
    $registration = EventRegistration::factory()->create(['event_id' => $event->id]);

    $this->actingAs($user)
        ->post(route('admin.events.registrations.bulk-update', $event), [
            'ids' => [$registration->id],
            'status' => 'attended',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('admin.events.registrations.bulk-destroy', $event), [
            'ids' => [$registration->id],
        ])
        ->assertForbidden();
});

it('allows admin to bulk confirm registrations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    $registrations = EventRegistration::factory()->count(2)->create([
        'event_id' => $event->id,
        'status' => 'registered',
    ]);

    $ids = $registrations->pluck('id')->toArray();

    $this->actingAs($admin)
        ->post(route('admin.events.registrations.bulk-update', $event), [
            'ids' => $ids,
            'status' => 'confirmed',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    foreach ($registrations as $registration) {
        $registration->refresh();
        expect($registration->status)->toBe('confirmed');
    }
});

it('allows admin to bulk cancel registrations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    $registrations = EventRegistration::factory()->count(2)->create([
        'event_id' => $event->id,
        'status' => 'confirmed',
    ]);

    $ids = $registrations->pluck('id')->toArray();

    $this->actingAs($admin)
        ->post(route('admin.events.registrations.bulk-update', $event), [
            'ids' => $ids,
            'status' => 'cancelled',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    foreach ($registrations as $registration) {
        $registration->refresh();
        expect($registration->status)->toBe('cancelled');
    }
});

it('allows admin to bulk mark attended and sets attended_at timestamp', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);
    $event = Event::factory()->create();
    $registrations = EventRegistration::factory()->count(2)->create([
        'event_id' => $event->id,
        'status' => 'confirmed',
        'attended_at' => null,
    ]);

    $ids = $registrations->pluck('id')->toArray();

    $this->actingAs($admin)
        ->post(route('admin.events.registrations.bulk-update', $event), [
            'ids' => $ids,
            'status' => 'attended',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    foreach ($registrations as $registration) {
        $registration->refresh();
        expect($registration->status)->toBe('attended');
        expect($registration->attended_at)->not->toBeNull();
    }
});
