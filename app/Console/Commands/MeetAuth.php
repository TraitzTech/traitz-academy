<?php

namespace App\Console\Commands;

use Google\Client;
use Google\Service\Calendar;
use Illuminate\Console\Command;

class MeetAuth extends Command
{
    protected $signature = 'meet:auth {--code= : Authorization code from Google (skips the interactive prompt)}';

    protected $description = 'Generate the academy Google account refresh token (Calendar scope) used to auto-create Google Meet links.';

    public function handle(): int
    {
        $clientId = (string) config('services.google_meet.client_id');
        $clientSecret = (string) config('services.google_meet.client_secret');
        $redirectUri = (string) config('services.google_meet.redirect_uri');

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Set GOOGLE_MEET_CLIENT_ID and GOOGLE_MEET_CLIENT_SECRET (or the YOUTUBE_* fallbacks) in your .env first.');

            return self::FAILURE;
        }

        $client = new Client;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setScopes([Calendar::CALENDAR_EVENTS]);

        $code = trim((string) $this->option('code'));

        if ($code === '') {
            $this->newLine();
            $this->info('1. Ensure this redirect URI is registered on your OAuth client in Google Cloud, and that the Calendar API is enabled:');
            $this->line('   '.$redirectUri);
            $this->newLine();
            $this->info('2. Sign in as the ACADEMY Google account (the one that should own every class meeting), open this URL, and approve:');
            $this->newLine();
            $this->line($client->createAuthUrl());
            $this->newLine();
            $this->info("3. Google redirects to {$redirectUri}?code=...  — copy the code value from the address bar and paste it below.");
            $this->newLine();
            $code = trim((string) $this->ask('Authorization code'));
        }

        if ($code === '') {
            $this->error('No authorization code provided.');

            return self::FAILURE;
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $this->error('Token exchange failed: '.($token['error_description'] ?? $token['error']));

            return self::FAILURE;
        }

        $refreshToken = $token['refresh_token'] ?? null;

        if (! $refreshToken) {
            $this->error('No refresh token was returned.');
            $this->line('Revoke the app at https://myaccount.google.com/permissions and run this again — Google only issues a refresh token on first consent.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Success! Add (or replace) this line in your .env:');
        $this->newLine();
        $this->line('GOOGLE_MEET_REFRESH_TOKEN='.$refreshToken);
        $this->newLine();
        $this->comment('Then run:  php artisan config:clear');

        return self::SUCCESS;
    }
}
