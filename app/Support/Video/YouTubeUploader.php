<?php

namespace App\Support\Video;

use Google\Client;
use Google\Exception as GoogleClientException;
use Google\Http\MediaFileUpload;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use RuntimeException;

/**
 * Uploads lesson videos to YouTube via the Data API.
 *
 * NOTE (host choice): YouTube is the current video host. A dedicated host such
 * as Bunny Stream / Cloudflare Stream is the planned upgrade — it avoids two
 * YouTube limitations: (1) API-uploaded videos are forced to `private` until
 * the Google Cloud project passes YouTube's API compliance audit (private
 * videos can't be embedded for learners), and (2) residual YouTube branding on
 * the embed. When we switch, add a sibling uploader with the same
 * upload()/delete() shape and swap it in at CourseLessonVideoController — the
 * lesson already stores a generic `video_url`, so the player needs little else.
 */
class YouTubeUploader
{
    private const UPLOAD_CHUNK_BYTES = 5 * 1024 * 1024;

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

        $authenticated = $this->makeAuthenticatedService();
        $client = $authenticated['client'];
        $service = $authenticated['service'];

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

        $insertParams = [
            'notifySubscribers' => (bool) config('services.youtube.notify_subscribers', false),
        ];

        $fileSize = filesize($filePath);
        if ($fileSize === false) {
            throw new RuntimeException('Could not read video file size.');
        }

        $mimeType = mime_content_type($filePath) ?: 'video/mp4';

        $client->setDefer(true);

        try {
            $request = $service->videos->insert(
                'snippet,status',
                $video,
                $insertParams
            );

            $media = new MediaFileUpload(
                $client,
                $request,
                $mimeType,
                null,
                true,
                self::UPLOAD_CHUNK_BYTES
            );
            $media->setFileSize($fileSize);

            $response = false;
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                throw new RuntimeException('Could not open video file for reading.');
            }

            try {
                while ($response === false && ! feof($handle)) {
                    $chunk = fread($handle, self::UPLOAD_CHUNK_BYTES);
                    if ($chunk === false) {
                        break;
                    }
                    if ($chunk === '' && feof($handle)) {
                        break;
                    }
                    $response = $media->nextChunk($chunk);
                }
            } finally {
                fclose($handle);
            }
        } catch (GoogleServiceException $exception) {
            throw new RuntimeException('YouTube upload failed: '.$exception->getMessage(), previous: $exception);
        } catch (GoogleClientException $exception) {
            throw new RuntimeException('YouTube upload failed: '.$exception->getMessage(), previous: $exception);
        } finally {
            $client->setDefer(false);
        }

        if (! $response instanceof Video) {
            throw new RuntimeException('YouTube upload did not complete (no video metadata returned).');
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

    public function delete(string $videoId): void
    {
        if (! (bool) config('services.youtube.enabled')) {
            throw new RuntimeException('YouTube uploads are disabled. Set YOUTUBE_ENABLED=true.');
        }

        $videoId = trim($videoId);
        if ($videoId === '') {
            return;
        }

        $authenticated = $this->makeAuthenticatedService();
        $service = $authenticated['service'];

        try {
            $service->videos->delete($videoId);
        } catch (GoogleServiceException $exception) {
            throw new RuntimeException('Could not delete previous YouTube video: '.$exception->getMessage(), previous: $exception);
        } catch (GoogleClientException $exception) {
            throw new RuntimeException('Could not delete previous YouTube video: '.$exception->getMessage(), previous: $exception);
        }
    }

    /**
     * @return array{client: Client, service: YouTube}
     */
    private function makeAuthenticatedService(): array
    {
        $clientId = (string) config('services.youtube.client_id');
        $clientSecret = (string) config('services.youtube.client_secret');
        $refreshToken = (string) config('services.youtube.refresh_token');

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            throw new RuntimeException('YouTube is not fully configured. Set YOUTUBE_CLIENT_ID, YOUTUBE_CLIENT_SECRET, and YOUTUBE_REFRESH_TOKEN.');
        }

        $client = new Client;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');
        // YOUTUBE_UPLOAD lets us upload; YOUTUBE_FORCE_SSL is additionally
        // required to delete videos (e.g. when replacing a lesson's video).
        // NOTE: the OAuth refresh token must be regenerated with BOTH scopes —
        // adding a scope here does not widen an already-issued token.
        $client->setScopes([YouTube::YOUTUBE_UPLOAD, YouTube::YOUTUBE_FORCE_SSL]);

        $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);
        if (! isset($token['access_token'])) {
            $message = trim(
                ($token['error'] ?? 'oauth_error').' '.($token['error_description'] ?? '')
            );

            throw new RuntimeException(
                'YouTube OAuth failed (check refresh token and API client): '.$message
            );
        }

        return [
            'client' => $client,
            'service' => new YouTube($client),
        ];
    }
}
