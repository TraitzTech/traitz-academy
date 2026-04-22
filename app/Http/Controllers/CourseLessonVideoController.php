<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Support\Video\YouTubeUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class CourseLessonVideoController extends Controller
{
    public function store(
        Request $request,
        Course $course,
        CourseSection $section,
        CourseLesson $lesson,
        YouTubeUploader $uploader
    ): RedirectResponse {
        $this->authorizeActor($course, $request->user());
        $this->authorizeSection($course, $section);
        $this->authorizeLesson($section, $lesson);

        abort_unless($lesson->type === 'video', 422, 'You can only upload files for video lessons.');

        $validated = $request->validate([
            'video_file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm', 'max:512000'],
        ]);

        $uploadedFile = $validated['video_file'];
        $absolutePath = $uploadedFile->getRealPath();
        if ($absolutePath === false) {
            return back()->withErrors(['video_file' => 'Could not read uploaded video file.']);
        }

        $lesson->update([
            'youtube_status' => 'processing',
            'youtube_error' => null,
        ]);

        try {
            if ($lesson->youtube_video_id) {
                $uploader->delete((string) $lesson->youtube_video_id);
            }
        } catch (Throwable $exception) {
            $lesson->update([
                'youtube_status' => 'failed',
                'youtube_error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'video_file' => 'Could not replace previous YouTube video: '.$exception->getMessage(),
            ]);
        }

        try {
            $result = $uploader->upload(
                $absolutePath,
                $lesson->title,
                $lesson->description
            );
        } catch (Throwable $exception) {
            $lesson->update([
                'youtube_status' => 'failed',
                'youtube_error' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'video_file' => 'YouTube upload failed: '.$exception->getMessage(),
            ]);
        }

        $lesson->update([
            'video_url' => $result['url'],
            'youtube_video_id' => $result['video_id'],
            'youtube_status' => 'ready',
            'youtube_error' => null,
        ]);

        return back()->with('success', 'Video uploaded to YouTube successfully.');
    }

    private function authorizeActor(Course $course, $actor): void
    {
        if ($actor?->canAccessAdminPanel()) {
            return;
        }

        abort_unless($actor?->isTutor(), 403, 'You are not allowed to upload lesson videos.');
        abort_unless((int) $course->instructor_id === (int) $actor->id, 403, 'You can only upload videos for your own courses.');
    }

    private function authorizeSection(Course $course, CourseSection $section): void
    {
        abort_unless((int) $section->course_id === (int) $course->id, 403);
    }

    private function authorizeLesson(CourseSection $section, CourseLesson $lesson): void
    {
        abort_unless((int) $lesson->course_section_id === (int) $section->id, 403);
    }
}
