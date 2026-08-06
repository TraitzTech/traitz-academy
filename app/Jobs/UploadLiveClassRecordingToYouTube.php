<?php

namespace App\Jobs;

use App\Models\LiveClassRecording;
use App\Support\Video\YouTubeUploader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadLiveClassRecordingToYouTube implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $recordingId
    ) {}

    public function handle(YouTubeUploader $uploader): void
    {
        $recording = LiveClassRecording::query()
            ->with('liveClass')
            ->find($this->recordingId);

        if (! $recording || ! $recording->file_path) {
            return;
        }

        $recording->update(['status' => 'processing']);

        $absolutePath = Storage::disk('public')->path($recording->file_path);

        try {
            $result = $uploader->upload(
                $absolutePath,
                $recording->liveClass?->title ?? 'Live Class Recording',
                $recording->liveClass?->description
            );

            $recording->update([
                'youtube_url' => $result['url'],
                'status' => 'uploaded',
            ]);
        } catch (\Throwable $exception) {
            $recording->update(['status' => 'failed']);

            Log::warning('Live class recording upload failed', [
                'recording_id' => $recording->id,
                'live_class_id' => $recording->live_class_id,
                'path' => $recording->file_path,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
