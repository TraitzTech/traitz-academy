<?php

use App\Jobs\Tac\NotifyActivityMembersJob;
use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Models\TacTrack;
use App\Models\User;
use App\Notifications\Tac\NewActivityNotification;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

uses(RefreshDatabase::class);

function tacExecutiveUser(): User
{
    return User::factory()->create(['role' => User::ROLE_CTO]);
}

/**
 * Swaps the mail factory bound in the container for one whose mailer always
 * throws, so `$member->notify(...)` goes down the same failure path a real
 * SMTP rate limit does — without touching a real mail server.
 */
function bindFailingMailer(\Throwable $exception): void
{
    $mailer = new class($exception) implements MailerContract
    {
        public function __construct(private \Throwable $exception) {}

        public function to($users) {}

        public function bcc($users) {}

        public function raw($text, $callback) {}

        public function send($view, array $data = [], $callback = null)
        {
            throw $this->exception;
        }

        public function sendNow($mailable, array $data = [], $callback = null) {}
    };

    $factory = new class($mailer) implements MailFactory
    {
        public function __construct(private MailerContract $mailer) {}

        public function mailer($name = null)
        {
            return $this->mailer;
        }
    };

    app()->instance(MailFactory::class, $factory);
}

// ----------------------------------------------------- Controller triggers

it('notifies only the mailable members of the activity track when created published', function () {
    Queue::fake();

    [$trackA, $trackB] = TacTrack::factory()->count(2)->create();

    $inTrackA = CommunityMember::factory()->create();
    $inTrackA->tracks()->attach($trackA);

    $optedOutInTrackA = CommunityMember::factory()->optedOut()->create();
    $optedOutInTrackA->tracks()->attach($trackA);

    $inTrackB = CommunityMember::factory()->create();
    $inTrackB->tracks()->attach($trackB);

    $this->actingAs(tacExecutiveUser())->post(route('admin.community.activities.store'), [
        'title' => 'Track A Workshop',
        'type' => TacActivity::TYPE_WORKSHOP,
        'tac_track_id' => $trackA->id,
        'location_type' => 'physical',
        'location' => 'Buea',
        'status' => TacActivity::STATUS_PUBLISHED,
    ])->assertRedirect();

    $activity = TacActivity::query()->where('title', 'Track A Workshop')->firstOrFail();

    expect($activity->members_notified_at)->not->toBeNull();

    Queue::assertPushed(NotifyActivityMembersJob::class, function (NotifyActivityMembersJob $job) use ($activity, $inTrackA, $optedOutInTrackA, $inTrackB) {
        return $job->activityId === $activity->id
            && in_array($inTrackA->id, $job->memberIds, true)
            && ! in_array($optedOutInTrackA->id, $job->memberIds, true)
            && ! in_array($inTrackB->id, $job->memberIds, true);
    });
});

it('notifies all mailable members when the activity has no track', function () {
    Queue::fake();

    $track = TacTrack::factory()->create();
    $memberInTrack = CommunityMember::factory()->create();
    $memberInTrack->tracks()->attach($track);
    $memberWithNoTrack = CommunityMember::factory()->create();

    $this->actingAs(tacExecutiveUser())->post(route('admin.community.activities.store'), [
        'title' => 'Community-wide Bootcamp',
        'type' => TacActivity::TYPE_BOOTCAMP,
        'location_type' => 'virtual',
        'meeting_url' => 'https://meet.example.test/bootcamp',
        'status' => TacActivity::STATUS_PUBLISHED,
    ])->assertRedirect();

    $activity = TacActivity::query()->where('title', 'Community-wide Bootcamp')->firstOrFail();

    Queue::assertPushed(NotifyActivityMembersJob::class, function (NotifyActivityMembersJob $job) use ($activity, $memberInTrack, $memberWithNoTrack) {
        return $job->activityId === $activity->id
            && in_array($memberInTrack->id, $job->memberIds, true)
            && in_array($memberWithNoTrack->id, $job->memberIds, true);
    });
});

it('does not notify anyone when an activity is created as a draft', function () {
    Queue::fake();

    CommunityMember::factory()->create();

    $this->actingAs(tacExecutiveUser())->post(route('admin.community.activities.store'), [
        'title' => 'Draft Handout',
        'type' => TacActivity::TYPE_HANDOUT,
        'location_type' => 'physical',
        'location' => 'Buea',
        'status' => TacActivity::STATUS_DRAFT,
    ])->assertRedirect();

    Queue::assertNothingPushed();
});

it('notifies members once a draft activity is published, and not again on a later status change', function () {
    Queue::fake();

    CommunityMember::factory()->count(2)->create();

    $activity = TacActivity::factory()->draft()->create(['tac_track_id' => null]);
    $executive = tacExecutiveUser();

    $this->actingAs($executive)
        ->post(route('admin.community.activities.status', $activity), ['status' => 'published'])
        ->assertRedirect();

    Queue::assertPushedTimes(NotifyActivityMembersJob::class, 1);

    // Flip it back to draft and republish — already notified, so it must
    // not fire a second round of emails.
    $this->actingAs($executive)
        ->post(route('admin.community.activities.status', $activity), ['status' => 'draft']);
    $this->actingAs($executive)
        ->post(route('admin.community.activities.status', $activity), ['status' => 'published']);

    Queue::assertPushedTimes(NotifyActivityMembersJob::class, 1);
});

// ------------------------------------------------------------- Job batching

it('sends to every member in a small batch without rescheduling anything', function () {
    Notification::fake();
    Queue::fake();

    $activity = TacActivity::factory()->create(['tac_track_id' => null]);
    $members = CommunityMember::factory()->count(3)->create();

    (new NotifyActivityMembersJob($activity->id, $members->pluck('id')->all()))->handle();

    foreach ($members as $member) {
        Notification::assertSentTo($member, NewActivityNotification::class);
    }

    Queue::assertNothingPushed();
});

it('chunks a large recipient list and immediately re-dispatches the rest', function () {
    Notification::fake();
    Queue::fake();

    $activity = TacActivity::factory()->create(['tac_track_id' => null]);
    $members = CommunityMember::factory()->count(20)->create();
    $memberIds = $members->pluck('id')->all();

    (new NotifyActivityMembersJob($activity->id, $memberIds))->handle();

    Notification::assertSentTimes(NewActivityNotification::class, 15);

    Queue::assertPushed(NotifyActivityMembersJob::class, function (NotifyActivityMembersJob $job) use ($activity, $memberIds) {
        return $job->activityId === $activity->id && count($job->memberIds) === count($memberIds) - 15;
    });
});

it('skips a member who unsubscribed between dispatch and the job actually running', function () {
    Notification::fake();

    $activity = TacActivity::factory()->create(['tac_track_id' => null]);
    $stillMailable = CommunityMember::factory()->create();
    $unsubscribedSince = CommunityMember::factory()->create();

    // Dispatch is decided from a snapshot of ids; the opt-out happens after.
    $memberIds = [$stillMailable->id, $unsubscribedSince->id];
    $unsubscribedSince->update(['email_opt_in' => false]);

    (new NotifyActivityMembersJob($activity->id, $memberIds))->handle();

    Notification::assertSentTo($stillMailable, NewActivityNotification::class);
    Notification::assertNotSentTo($unsubscribedSince, NewActivityNotification::class);
});

it('logs and continues past a non-rate-limit send failure instead of stopping the batch', function () {
    Queue::fake();
    Log::spy();

    bindFailingMailer(new RuntimeException('Some unrelated mail failure'));

    $activity = TacActivity::factory()->create(['tac_track_id' => null]);
    $members = CommunityMember::factory()->count(3)->create();

    (new NotifyActivityMembersJob($activity->id, $members->pluck('id')->all()))->handle();

    Log::shouldHaveReceived('warning')
        ->with('Could not notify community member about new activity', \Mockery::type('array'))
        ->times(3);

    // Not a rate limit, so nothing gets rescheduled.
    Queue::assertNothingPushed();
});

it('reschedules the rest of the run for a day later when the mailer rate-limits it', function () {
    Queue::fake();

    bindFailingMailer(new UnexpectedResponseException(
        'Expected response code "250" but got code "451", with message "451 4.7.1 Ratelimit "hostinger_out_ratelimit" exceeded for key "abc"'
    ));

    $activity = TacActivity::factory()->create(['tac_track_id' => null]);
    $members = CommunityMember::factory()->count(3)->create();
    $memberIds = $members->pluck('id')->all();

    (new NotifyActivityMembersJob($activity->id, $memberIds))->handle();

    Queue::assertPushed(NotifyActivityMembersJob::class, function (NotifyActivityMembersJob $job) use ($activity, $memberIds) {
        return $job->activityId === $activity->id
            && count($job->memberIds) === count($memberIds)
            && $job->delay instanceof DateTimeInterface
            && now()->diffInHours($job->delay) >= 23;
    });
});
