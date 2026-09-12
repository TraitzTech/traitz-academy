<?php

namespace App\Jobs\Tac;

use App\Models\CommunityMember;
use App\Models\TacActivity;
use App\Notifications\Tac\NewActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Emails community members about a newly published activity in small
 * batches instead of one long-running send: each run handles at most
 * CHUNK_SIZE recipients then re-dispatches itself for the rest, so a big
 * membership never outruns a short-lived cron queue worker.
 *
 * If a send fails because the mail provider's rate limit was hit (the
 * failure mode that used to crash the forgot-password request — see
 * ResetPasswordNotification), the whole remaining list — this batch's
 * leftovers plus everything queued after it — is rescheduled for a day
 * later instead of being lost or retried immediately into the same limit.
 */
class NotifyActivityMembersJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const CHUNK_SIZE = 15;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  array<int, int>  $memberIds  Community member IDs still to notify.
     */
    public function __construct(
        public int $activityId,
        public array $memberIds,
    ) {}

    public function handle(): void
    {
        $activity = TacActivity::query()->find($this->activityId);

        if (! $activity || $this->memberIds === []) {
            return;
        }

        $batchIds = array_slice($this->memberIds, 0, self::CHUNK_SIZE);
        $laterIds = array_slice($this->memberIds, self::CHUNK_SIZE);

        // Re-check mailable() now, not just at dispatch time, so someone who
        // unsubscribed while this run was waiting in the queue is skipped.
        $members = CommunityMember::query()
            ->whereIn('id', $batchIds)
            ->mailable()
            ->get()
            ->values();

        $rateLimitedFrom = [];

        foreach ($members as $index => $member) {
            try {
                $member->notify(new NewActivityNotification($activity));
            } catch (\Throwable $e) {
                if ($this->isRateLimited($e)) {
                    $rateLimitedFrom = $members->slice($index)->pluck('id')->all();
                    break;
                }

                Log::warning('Could not notify community member about new activity', [
                    'activity_id' => $activity->id,
                    'community_member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($rateLimitedFrom !== []) {
            $stillToSend = [...$rateLimitedFrom, ...$laterIds];

            self::dispatch($this->activityId, $stillToSend)->delay(now()->addDay());

            Log::info('Activity notification run hit the mailer rate limit; rest rescheduled for tomorrow', [
                'activity_id' => $activity->id,
                'remaining' => count($stillToSend),
            ]);

            return;
        }

        if ($laterIds !== []) {
            self::dispatch($this->activityId, $laterIds);
        }
    }

    private function isRateLimited(\Throwable $e): bool
    {
        if (! $e instanceof TransportExceptionInterface) {
            return false;
        }

        $message = mb_strtolower($e->getMessage());

        return str_contains($message, 'ratelimit') || str_contains($message, '451');
    }
}
