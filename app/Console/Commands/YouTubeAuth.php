<?php

namespace App\Console\Commands;

use Google\Client;
use Google\Service\YouTube;
use Illuminate\Console\Command;

class YouTubeAuth extends Command
{
    protected $signature = 'youtube:auth {--code= : Authorization code from Google (skips the interactive prompt)}';

    protected $description = 'Generate a YouTube OAuth refresh token with upload + delete (force-ssl) scopes.';

    public function handle(): int
    {
        $clientId = (string) config('services.youtube.client_id');
        $clientSecret = (string) config('services.youtube.client_secret');
        $redirectUri = (string) config('services.youtube.redirect_uri');

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Set YOUTUBE_CLIENT_ID and YOUTUBE_CLIENT_SECRET in your .env first.');

            return self::FAILURE;
        }

        $client = new Client;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // force Google to return a refresh token every time
        $client->setScopes([YouTube::YOUTUBE_UPLOAD, YouTube::YOUTUBE_FORCE_SSL]);

        $code = trim((string) $this->option('code'));

        if ($code === '') {
            $this->newLine();
            $this->info('1. Make sure this redirect URI is registered on your OAuth client in Google Cloud:');
            $this->line('   '.$redirectUri);
            $this->newLine();
            $this->info('2. Open this URL in a browser signed in as the channel owner, and approve access:');
            $this->newLine();
            $this->line($client->createAuthUrl());
            $this->newLine();
            $this->info("3. Google redirects to {$redirectUri}?code=...  (the page may fail to load — that's fine).");
            $this->info('   Copy the "code" value from the address bar and paste it below.');
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
        $this->line('YOUTUBE_REFRESH_TOKEN='.$refreshToken);
        $this->newLine();
        $this->comment('Then run:  php artisan config:clear');

        return self::SUCCESS;
    }
}
