<?php

use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\User;
use App\Notifications\Internships\LogbookReminderNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    // Pin "today" to a known Monday so working-day math is deterministic.
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-08-10 09:00:00')); // a Monday
});

afterEach(function () {
    Carbon\Carbon::setTestNow();
});

it('reminds an intern with no logbook entry today on a working day', function () {
    Notification::fake();

    $user = User::factory()->create();
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $this->artisan('internship:send-logbook-reminders')->assertSuccessful();

    Notification::assertSentTo($user, LogbookReminderNotification::class);
});

it('does not remind an intern who already submitted today', function () {
    Notification::fake();

    $user = User::factory()->create();
    $internship = Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);
    LogbookEntry::factory()->submitted()->create([
        'internship_id' => $internship->id,
        'date' => now()->toDateString(),
    ]);

    $this->artisan('internship:send-logbook-reminders')->assertSuccessful();

    Notification::assertNotSentTo($user, LogbookReminderNotification::class);
});

it('does not remind anyone on a weekend', function () {
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-08-08 09:00:00')); // a Saturday
    Notification::fake();

    $user = User::factory()->create();
    Internship::factory()->create(['user_id' => $user->id, 'status' => 'active']);

    $this->artisan('internship:send-logbook-reminders')->assertSuccessful();

    Notification::assertNotSentTo($user, LogbookReminderNotification::class);
});

it('computes missed logbook days as working days elapsed minus submitted days', function () {
    $user = User::factory()->create();
    // Started the Monday before last (2026-08-03) — 6 working days elapsed
    // through today (2026-08-10, inclusive): Mon..Fri + this Monday.
    $internship = Internship::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'start_date' => '2026-08-03',
        'end_date' => now()->addMonth()->toDateString(),
    ]);

    LogbookEntry::factory()->submitted()->create(['internship_id' => $internship->id, 'date' => '2026-08-03']);
    LogbookEntry::factory()->submitted()->create(['internship_id' => $internship->id, 'date' => '2026-08-04']);

    expect($internship->workingDaysElapsed())->toBe(6);
    expect($internship->missedLogbookDaysCount())->toBe(4);
});

it('counts a submission on a non-working day toward submitted days without going negative on missed days', function () {
    // Placed on a Saturday, "today" is still the weekend — zero working days
    // have elapsed, but the intern already submitted an entry that day.
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-08-09 22:00:00')); // a Sunday

    $user = User::factory()->create();
    $internship = Internship::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'start_date' => '2026-08-08',
        'logbook_starts_on' => '2026-08-08',
        'end_date' => now()->addMonth()->toDateString(),
    ]);

    LogbookEntry::factory()->submitted()->create(['internship_id' => $internship->id, 'date' => '2026-08-08']);

    expect($internship->workingDaysElapsed())->toBe(0);
    expect($internship->submittedLogbookDaysCount())->toBe(1);
    expect($internship->missedLogbookDaysCount())->toBe(0);
});
