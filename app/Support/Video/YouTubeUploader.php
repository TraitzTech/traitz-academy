<?php

namespace App\Support\Video;

use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use RuntimeException;

class YouTubeUploader
{
    /**
     * @return array{video_id: string, url: string}
     */
    public function upload(string $filePath, string $title, ?string $description = null): array
    {
        if (! (bool) config('services.youtube.enabled')) {
            throw new RuntimeException('YouTube uploads are disabled. Set YOUTUBE_ENABLED=true.');
        }

        if (! is_file($filePath)) {
            throw new RuntimeException('Video file not found for upload.');
        }

        $client = new Client;
        $client->setClientId((string) config('services.youtube.client_id'));
        $client->setClientSecret((string) config('services.youtube.client_secret'));
        $client->setAccessType('offline');
        $client->setScopes([YouTube::YOUTUBE_UPLOAD]);
        $client->setAccessToken(['refresh_token' => (string) config('services.youtube.refresh_token')]);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken((string) config('services.youtube.refresh_token'));
        }

        $service = new YouTube($client);

        $snippet = new VideoSnippet;
        $snippet->setTitle($title);
        $snippet->setDescription((string) ($description ?? ''));
        $snippet->setCategoryId((string) config('services.youtube.category_id', '27'));

        $status = new VideoStatus;
        $status->setPrivacyStatus((string) config('services.youtube.privacy_status', 'unlisted'));
        $status->setSelfDeclaredMadeForKids(false);

        $video = new Video;
        $video->setSnippet($snippet);
        $video->setStatus($status);

        try {
            $response = $service->videos->insert(
                'snippet,status',
                $video,
                [
                    'data' => file_get_contents($filePath),
                    'mimeType' => mime_content_type($filePath) ?: 'application/octet-stream',
                    'uploadType' => 'multipart',
                    'notifySubscribers' => (bool) config('services.youtube.notify_subscribers', false),
                ]
            );
        } catch (GoogleServiceException $exception) {
            throw new RuntimeException('YouTube upload failed: '.$exception->getMessage(), previous: $exception);
        }

        $videoId = (string) ($response->id ?? '');
        if ($videoId === '') {
            throw new RuntimeException('YouTube upload did not return a video ID.');
        }

        return [
            'video_id' => $videoId,
            'url' => 'https://www.youtube.com/watch?v='.$videoId,
        ];
    }
}
