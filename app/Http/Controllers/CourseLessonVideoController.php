<?php

namespace App\Http\Controllers;

use App\Jobs\UploadLessonVideoToYouTube;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseLessonVideoController extends Controller
{
    public function store(Request $request, Course $course, CourseSection $section, CourseLesson $lesson): RedirectResponse
    {
        $this->authorizeActor($course, $request->user());
        $this->authorizeSection($course, $section);
        $this->authorizeLesson($section, $lesson);

        abort_unless($lesson->type === 'video', 422, 'You can only upload files for video lessons.');

        $validated = $request->validate([
            'video_file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm', 'max:512000'],
        ]);

        $path = $validated['video_file']->store('lesson-video-uploads', 'local');

        $lesson->update([
            'youtube_status' => 'pending',
            'youtube_error' => null,
        ]);

        UploadLessonVideoToYouTube::dispatch((int) $lesson->id, $path);

        return back()->with('success', 'Video received and queued for YouTube upload.');
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
