<?php

namespace App\Jobs;

use App\Models\CourseLesson;
use App\Support\Video\YouTubeUploader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadLessonVideoToYouTube implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  string  $tempPath  Relative path on local disk.
     */
    public function __construct(
        public int $lessonId,
        public string $tempPath,
    ) {}

    public function handle(YouTubeUploader $uploader): void
    {
        $lesson = CourseLesson::query()->find($this->lessonId);
        if ($lesson === null) {
            Storage::disk('local')->delete($this->tempPath);

            return;
        }

        $absolutePath = Storage::disk('local')->path($this->tempPath);

        $lesson->update([
            'youtube_status' => 'processing',
            'youtube_error' => null,
        ]);

        try {
            $result = $uploader->upload(
                $absolutePath,
                $lesson->title,
                $lesson->description
            );

            $lesson->update([
                'video_url' => $result['url'],
                'youtube_video_id' => $result['video_id'],
                'youtube_status' => 'ready',
                'youtube_error' => null,
            ]);
        } catch (\Throwable $exception) {
            $lesson->update([
                'youtube_status' => 'failed',
                'youtube_error' => $exception->getMessage(),
            ]);

            Log::warning('YouTube lesson upload failed', [
                'lesson_id' => $lesson->id,
                'course_id' => $lesson->course_id,
                'path' => $this->tempPath,
                'message' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);

            throw $exception;
        } finally {
            Storage::disk('local')->delete($this->tempPath);
        }
    }
}
