<?php

namespace App\Support\LiveClass;

use Carbon\CarbonInterface;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Creates Google Meet links via the Calendar API using one central "academy"
 * Google account. On the free account tier the links carry Google's 60-minute
 * group-call limit (learners simply rejoin the same link); upgrading only that
 * one account to Workspace later removes the limit with no code change.
 */
class GoogleMeetService
{
    public function isConfigured(): bool
    {
        return $this->clientId() !== ''
            && $this->clientSecret() !== ''
            && $this->refreshToken() !== '';
    }

    /**
     * @return array{meeting_url: string, event_id: string}
     */
    public function createMeeting(string $title, CarbonInterface $start, int $durationMinutes, ?string $description = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Google Meet is not configured. Set GOOGLE_MEET_* and run `php artisan meet:auth`.');
        }

        $service = new Calendar($this->authenticatedClient());
        $end = $start->copy()->addMinutes(max(1, $durationMinutes));

        $event = new Event([
            'summary' => $title,
            'description' => (string) ($description ?? ''),
            'start' => new EventDateTime([
                'dateTime' => $start->toRfc3339String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ]),
            'end' => new EventDateTime([
                'dateTime' => $end->toRfc3339String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ]),
            'conferenceData' => new ConferenceData([
                'createRequest' => new CreateConferenceRequest([
                    'requestId' => (string) Str::uuid(),
                    'conferenceSolutionKey' => new ConferenceSolutionKey([
                        'type' => 'hangoutsMeet',
                    ]),
                ]),
            ]),
        ]);

        try {
            $created = $service->events->insert(
                (string) config('services.google_meet.calendar_id', 'primary'),
                $event,
                ['conferenceDataVersion' => 1]
            );
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not create the Google Meet link: '.$exception->getMessage(), previous: $exception);
        }

        $meetingUrl = (string) ($created->getHangoutLink() ?? '');
        if ($meetingUrl === '') {
            throw new RuntimeException('Google did not return a Meet link for the event.');
        }

        return [
            'meeting_url' => $meetingUrl,
            'event_id' => (string) $created->getId(),
        ];
    }

    /**
     * Best-effort removal of the backing calendar event. Never throws — a failed
     * cleanup should not block deleting a live class.
     */
    public function deleteMeeting(?string $eventId): void
    {
        $eventId = trim((string) $eventId);
        if ($eventId === '' || ! $this->isConfigured()) {
            return;
        }

        try {
            $service = new Calendar($this->authenticatedClient());
            $service->events->delete((string) config('services.google_meet.calendar_id', 'primary'), $eventId);
        } catch (Throwable) {
            // Orphaned calendar event is acceptable; ignore.
        }
    }

    private function authenticatedClient(): Client
    {
        $client = new Client;
        $client->setClientId($this->clientId());
        $client->setClientSecret($this->clientSecret());
        $client->setAccessType('offline');
        $client->setScopes([Calendar::CALENDAR_EVENTS]);

        $token = $client->fetchAccessTokenWithRefreshToken($this->refreshToken());
        if (! isset($token['access_token'])) {
            $message = trim(($token['error'] ?? 'oauth_error').' '.($token['error_description'] ?? ''));

            throw new RuntimeException('Google Meet OAuth failed (check refresh token and API client): '.$message);
        }

        return $client;
    }

    private function clientId(): string
    {
        return (string) config('services.google_meet.client_id');
    }

    private function clientSecret(): string
    {
        return (string) config('services.google_meet.client_secret');
    }

    private function refreshToken(): string
    {
        return (string) config('services.google_meet.refresh_token');
    }
}
