<?php

namespace App\Http\Controllers\Concerns;

use App\Models\LiveClass;
use App\Support\LiveClass\GoogleMeetService;
use Throwable;

trait GeneratesLiveClassMeeting
{
    /**
     * Ensure the live class has a meeting link.
     *
     * Precedence: an explicit manual URL always wins. Otherwise, when the
     * driver is 'meet' and the academy Google account is configured, we
     * auto-create a Google Meet link. Any failure is non-fatal — the class is
     * saved with no link and the tutor can paste one manually later.
     */
    protected function ensureMeetingLink(LiveClass $liveClass, ?string $manualUrl = null): void
    {
        if (filled($manualUrl)) {
            $liveClass->update([
                'meeting_url' => $manualUrl,
                'meeting_provider' => 'manual',
                'meeting_event_id' => null,
            ]);

            return;
        }

        // Don't overwrite an existing link on update.
        if (filled($liveClass->meeting_url)) {
            return;
        }

        if (config('services.live_class.driver') !== 'meet') {
            return;
        }

        $service = app(GoogleMeetService::class);
        if (! $service->isConfigured()) {
            return;
        }

        try {
            $result = $service->createMeeting(
                $liveClass->title,
                $liveClass->start_time,
                (int) $liveClass->duration,
                $liveClass->description,
            );

            $liveClass->update([
                'meeting_url' => $result['meeting_url'],
                'meeting_provider' => 'google_meet',
                'meeting_event_id' => $result['event_id'],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
