<?php

use App\Jobs\Tac\SyncCommunityMemberFromRegistration;
use App\Models\AiForgeRegistration;
use App\Models\Application;
use App\Models\CommunityMember;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\TacTrack;
use App\Models\User;
use App\Notifications\Tac\TacWelcomeNotification;
use App\Services\Tac\CommunityEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

it('queues an auto-join when someone registers for an event', function () {
    Queue::fake();

    EventRegistration::factory()->create();

    Queue::assertPushed(SyncCommunityMemberFromRegistration::class);
});

it('includes an event registrant in the community and welcomes them', function () {
    $event = Event::factory()->create(['title' => 'Tech Night Buea']);

    $registration = EventRegistration::factory()->create([
        'event_id' => $event->id,
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'Ada@Example.com',
    ]);

    $member = CommunityMember::query()->where('email', 'ada@example.com')->first();

    expect($member)->not->toBeNull()
        ->and($member->first_name)->toBe('Ada')
        ->and($member->last_name)->toBe('Lovelace')
        ->and($member->source)->toBe(CommunityMember::SOURCE_EVENT)
        ->and($member->sourceable_id)->toBe($registration->id)
        ->and($member->sourceable_type)->toBe(EventRegistration::class)
        ->and($member->joined_at)->not->toBeNull()
        ->and($member->welcomed_at)->not->toBeNull();

    Notification::assertSentTo($member, TacWelcomeNotification::class);
});

it('includes program applicants', function () {
    Application::factory()->create([
        'email' => 'applicant@example.com',
        'institution_name' => 'University of Buea',
    ]);

    $member = CommunityMember::query()->where('email', 'applicant@example.com')->first();

    expect($member)->not->toBeNull()
        ->and($member->source)->toBe(CommunityMember::SOURCE_PROGRAM_APPLICATION)
        ->and($member->school)->toBe('University of Buea');
});

it('includes AI Forge registrants', function () {
    AiForgeRegistration::factory()->create([
        'email' => 'ngozi@example.com',
        'organization' => 'Traitz Labs',
    ]);

    $member = CommunityMember::query()->where('email', 'ngozi@example.com')->first();

    expect($member)->not->toBeNull()
        ->and($member->source)->toBe(CommunityMember::SOURCE_AI_FORGE)
        ->and($member->school)->toBe('Traitz Labs');
});

it('includes course students, splitting their account name', function () {
    $user = User::factory()->create([
        'name' => 'Amina Boateng Sesay',
        'email' => 'amina@example.com',
    ]);

    Enrollment::factory()->create(['user_id' => $user->id]);

    $member = CommunityMember::query()->where('email', 'amina@example.com')->first();

    expect($member)->not->toBeNull()
        ->and($member->source)->toBe(CommunityMember::SOURCE_COURSE)
        ->and($member->first_name)->toBe('Amina')
        ->and($member->last_name)->toBe('Boateng Sesay')
        ->and($member->user_id)->toBe($user->id);
});

it('keeps one member when the same person registers for several things', function () {
    EventRegistration::factory()->create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'email' => 'grace@example.com',
        'phone' => '',
    ]);

    Application::factory()->create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'email' => 'grace@example.com',
        'phone' => '+237611111111',
        'institution_name' => 'University of Buea',
    ]);

    $members = CommunityMember::query()->where('email', 'grace@example.com')->get();

    expect($members)->toHaveCount(1);

    $member = $members->first();

    // The first registration wins the source; the later one fills the blanks.
    expect($member->source)->toBe(CommunityMember::SOURCE_EVENT)
        ->and($member->phone)->toBe('+237611111111')
        ->and($member->school)->toBe('University of Buea');

    // And only one welcome email, ever.
    Notification::assertSentToTimes($member, TacWelcomeNotification::class, 1);
});

it('never overwrites details the member has already curated', function () {
    $member = app(CommunityEnrollmentService::class)->record(
        email: 'curated@example.com',
        attributes: [
            'first_name' => 'Chinwe',
            'last_name' => 'Okonkwo',
            'phone' => '+237699999999',
            'school' => 'ICT University',
            'current_status' => CommunityMember::STATUS_PAST_INTERN,
        ],
        source: CommunityMember::SOURCE_JOIN_FORM,
        notify: false,
    );

    Application::factory()->create([
        'first_name' => 'C.',
        'last_name' => 'Okonkwo',
        'email' => 'curated@example.com',
        'phone' => '+237600000001',
        'institution_name' => 'Somewhere Else',
    ]);

    $member->refresh();

    expect($member->first_name)->toBe('Chinwe')
        ->and($member->phone)->toBe('+237699999999')
        ->and($member->school)->toBe('ICT University')
        ->and($member->current_status)->toBe(CommunityMember::STATUS_PAST_INTERN);
});

it('links a guest member to their account when they later sign up', function () {
    EventRegistration::factory()->create([
        'user_id' => null,
        'email' => 'kwame@example.com',
    ]);

    $member = CommunityMember::query()->where('email', 'kwame@example.com')->first();
    expect($member->user_id)->toBeNull();

    $user = User::factory()->create(['email' => 'kwame@example.com']);

    expect($member->fresh()->user_id)->toBe($user->id);
});

it('links a member when the account email later changes to theirs', function () {
    EventRegistration::factory()->create(['user_id' => null, 'email' => 'newmail@example.com']);
    $user = User::factory()->create(['email' => 'oldmail@example.com']);

    $user->update(['email' => 'newmail@example.com']);

    $member = CommunityMember::query()->where('email', 'newmail@example.com')->first();

    expect($member->user_id)->toBe($user->id);
});

it('ignores registrations without a usable email', function () {
    $enrollment = app(CommunityEnrollmentService::class);

    expect($enrollment->record(email: '', notify: false))->toBeNull()
        ->and($enrollment->record(email: 'not-an-email', notify: false))->toBeNull()
        ->and(CommunityMember::count())->toBe(0);
});

it('attaches tracks additively and marks the first one primary', function () {
    $enrollment = app(CommunityEnrollmentService::class);
    $tracks = TacTrack::query()->orderBy('sort_order')->take(3)->pluck('id')->all();

    $member = $enrollment->record(
        email: 'tracked@example.com',
        attributes: ['first_name' => 'Tunde'],
        trackIds: [$tracks[0], $tracks[1]],
        notify: false,
    );

    expect($member->tracks)->toHaveCount(2)
        ->and($member->tracks()->wherePivot('is_primary', true)->first()->id)->toBe($tracks[0]);

    // Re-recording with an overlapping set adds the new one, drops nothing.
    $enrollment->record(
        email: 'tracked@example.com',
        trackIds: [$tracks[1], $tracks[2]],
        notify: false,
    );

    expect($member->fresh()->tracks)->toHaveCount(3)
        ->and($member->fresh()->tracks()->wherePivot('is_primary', true)->count())->toBe(1);
});

it('does not email members who have opted out', function () {
    CommunityMember::create([
        'first_name' => 'Quiet',
        'email' => 'quiet@example.com',
        'email_opt_in' => false,
        'joined_at' => now(),
    ]);

    app(CommunityEnrollmentService::class)->record(
        email: 'quiet@example.com',
        attributes: ['first_name' => 'Quiet'],
        notify: true,
    );

    Notification::assertNothingSent();
});

it('seeds the eight TAC tracks', function () {
    expect(TacTrack::query()->active()->count())->toBe(8)
        ->and(TacTrack::query()->pluck('slug')->all())
        ->toContain('web-development', 'ai-machine-learning', 'arduino-iot');
});
