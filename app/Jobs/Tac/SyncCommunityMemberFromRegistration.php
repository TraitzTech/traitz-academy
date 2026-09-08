<?php

namespace App\Jobs\Tac;

use App\Services\Tac\CommunityEnrollmentService;
use App\Services\Tac\RegistrationMemberMapper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Auto-includes a registrant in TAC, off the request cycle so a slow mail
 * server can never make somebody's event signup hang or fail.
 */
class SyncCommunityMemberFromRegistration implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Model $registration,
        public bool $notify = true,
    ) {}

    public function handle(RegistrationMemberMapper $mapper, CommunityEnrollmentService $enrollment): void
    {
        $mapped = $mapper->map($this->registration);

        if ($mapped === null) {
            return;
        }

        $enrollment->record(
            email: $mapped['email'],
            attributes: $mapped['attributes'],
            source: $mapped['source'],
            sourceable: $this->registration,
            notify: $this->notify,
            context: $mapped['context'],
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TAC auto-join failed', [
            'model' => $this->registration::class,
            'id' => $this->registration->getKey(),
            'error' => $exception->getMessage(),
        ]);
    }
}
