<?php

namespace App\Support\Lms;

use App\Models\User;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use RuntimeException;

class GoogleCalendarSyncService
{
    public function authUrl(User $user): string
    {
        $client = $this->makeClient();
        $client->setState((string) $user->id);

        return $client->createAuthUrl();
    }

    /**
     * @return array{refresh_token:string,email:string|null}
     */
    public function exchangeCode(string $code): array
    {
        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (! isset($token['access_token'])) {
            throw new RuntimeException('Google OAuth exchange failed.');
        }

        if (! empty($token['refresh_token'])) {
            $refreshToken = (string) $token['refresh_token'];
        } else {
            throw new RuntimeException('Google did not return a refresh token. Reconnect and grant consent.');
        }

        $client->setAccessToken($token);
        $oauth = new \Google\Service\Oauth2($client);
        $profile = $oauth->userinfo->get();

        return [
            'refresh_token' => $refreshToken,
            'email' => $profile->getEmail(),
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $events
     */
    public function syncEvents(User $user, array $events): void
    {
        if (! $user->google_calendar_refresh_token) {
            throw new RuntimeException('Google Calendar is not connected.');
        }

        $client = $this->makeClient();
        $token = $client->fetchAccessTokenWithRefreshToken($user->google_calendar_refresh_token);
        if (! isset($token['access_token'])) {
            throw new RuntimeException('Google Calendar token refresh failed.');
        }

        $service = new Calendar($client);
        $calendarId = 'primary';

        foreach ($events as $item) {
            $eventId = $this->buildEventId((string) ($item['uid'] ?? ''));
            if ($eventId === '') {
                continue;
            }

            $event = new Event([
                'summary' => (string) $item['title'],
                'description' => (string) ($item['description'] ?? ''),
                'location' => (string) ($item['location'] ?? ''),
            ]);

            $start = new EventDateTime;
            $start->setDateTime((string) $item['starts_at']);
            $start->setTimeZone(config('app.timezone'));
            $event->setStart($start);

            $end = new EventDateTime;
            $end->setDateTime((string) ($item['ends_at'] ?? $item['starts_at']));
            $end->setTimeZone(config('app.timezone'));
            $event->setEnd($end);

            try {
                $current = $service->events->get($calendarId, $eventId);
                $event->setId($current->getId());
                $service->events->update($calendarId, $eventId, $event);
            } catch (\Throwable) {
                $event->setId($eventId);
                $service->events->insert($calendarId, $event);
            }
        }
    }

    private function makeClient(): Client
    {
        $clientId = (string) config('services.google_calendar.client_id');
        $clientSecret = (string) config('services.google_calendar.client_secret');
        $redirectUri = (string) config('services.google_calendar.redirect_uri');

        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            throw new RuntimeException('Google Calendar OAuth is not configured.');
        }

        $client = new Client;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([
            Calendar::CALENDAR_EVENTS,
            'email',
            'profile',
        ]);

        return $client;
    }

    private function buildEventId(string $uid): string
    {
        if ($uid === '') {
            return '';
        }

        return substr(preg_replace('/[^a-z0-9]/', '', strtolower($uid)) ?? '', 0, 100);
    }
}
