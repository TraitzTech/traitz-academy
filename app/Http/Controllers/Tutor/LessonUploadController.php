<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Support\Video\YouTubeUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LessonUploadController extends Controller
{
    public function index(): Response
    {
        $tutorId = auth()->id();

        // All tutor courses with their sections for the upload form
        $courses = Course::where('instructor_id', $tutorId)
            ->whereIn('status', ['draft', 'published'])
            ->with('sections:id,course_id,title')
            ->orderBy('title')
            ->get(['id', 'title', 'status']);

        // Recent lessons across all tutor courses
        $recentLessons = CourseLesson::whereHas(
            'course', fn ($q) => $q->where('instructor_id', $tutorId)
        )
            ->with('course:id,title', 'section:id,title')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'title' => $l->title,
                'type' => $l->type,
                'duration' => $l->duration,
                'course' => $l->course?->title,
                'section' => $l->section?->title,
                'is_free' => $l->is_free,
                'has_video' => ! empty($l->video_url),
                'created_at' => $l->created_at?->diffForHumans(),
            ]);

        return Inertia::render('Tutor/Lessons/Upload', [
            'courses' => $courses,
            'recentLessons' => $recentLessons,
        ]);
    }

    public function store(Request $request, YouTubeUploader $uploader): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'section_id' => ['required', 'exists:course_sections,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:video,text,quiz'],
            'description' => ['nullable', 'string', 'max:1000'],
            'duration' => ['nullable', 'string', 'max:20'],
            'is_free' => ['boolean'],
            'video_file' => ['nullable', 'required_if:type,video', 'file', 'mimes:mp4,mov,avi,mkv,webm,qt', 'max:512000'], // 500 MB
            'content' => ['nullable', 'string'],
        ]);

        // Authorise — tutor must own the course
        $course = Course::where('id', $validated['course_id'])
            ->where('instructor_id', auth()->id())
            ->firstOrFail();

        $videoUrl = null;
        $youtubeVideoId = null;
        $youtubeStatus = null;
        $youtubeError = null;

        if ($request->hasFile('video_file')) {
            $absolutePath = $request->file('video_file')->getRealPath();
            if ($absolutePath === false) {
                return back()->withErrors(['video_file' => 'Could not read uploaded video file.'])->withInput();
            }

            try {
                $result = $uploader->upload(
                    $absolutePath,
                    $validated['title'],
                    $validated['description'] ?? null
                );
            } catch (Throwable $exception) {
                return back()->withErrors([
                    'video_file' => 'YouTube upload failed: '.$exception->getMessage(),
                ])->withInput();
            }

            $videoUrl = $result['url'];
            $youtubeVideoId = $result['video_id'];
            $youtubeStatus = 'ready';
            $youtubeError = null;
        }

        $sortOrder = CourseLesson::where('course_section_id', $validated['section_id'])->max('sort_order') + 1;

        $lesson = CourseLesson::create([
            'course_id' => $validated['course_id'],
            'course_section_id' => $validated['section_id'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'video_url' => $videoUrl,
            'youtube_video_id' => $youtubeVideoId,
            'youtube_status' => $youtubeStatus,
            'youtube_error' => $youtubeError,
            'content' => $validated['content'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'is_free' => $validated['is_free'] ?? false,
            'sort_order' => $sortOrder,
        ]);

        return back()->with('success', "Lesson \"{$validated['title']}\" uploaded successfully.");
    }

    public function destroy(CourseLesson $lesson): RedirectResponse
    {
        // Authorise
        abort_unless(
            $lesson->course->instructor_id === auth()->id(),
            403, 'Unauthorized'
        );

        // Delete local video file if stored on disk
        if ($lesson->video_url && ! Str::startsWith($lesson->video_url, ['http://', 'https://'])) {
            Storage::disk('public')->delete($lesson->video_url);
        }

        $lesson->delete();

        return back()->with('success', 'Lesson deleted.');
    }
}
