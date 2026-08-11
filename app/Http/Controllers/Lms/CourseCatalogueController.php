<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Lms\Concerns\InteractsWithCourseContent;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLesson;
use App\Models\LessonNote;
use App\Models\LessonVideoProgress;
use App\Support\Lms\CourseProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseCatalogueController extends Controller
{
    use InteractsWithCourseContent;

    public function index(Request $request): Response
    {
        $courses = Course::query()
            ->published()
            ->with('instructor:id,name', 'category:id,name,slug,color')
            ->when($request->category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->when($request->level, fn ($q) => $q->where('level', $request->level))
            ->when($request->boolean('free'), fn ($q) => $q->where('price', '<=', 0))
            ->when($request->search, fn ($q) => $q->where(function ($builder) use ($request) {
                $builder->where('title', 'like', "%{$request->search}%")
                    ->orWhere('short_description', 'like', "%{$request->search}%");
            }))
            ->when($request->sort === 'popular', fn ($q) => $q->orderByDesc('enrolled_count'))
            ->when($request->sort === 'rating', fn ($q) => $q->orderByDesc('rating'))
            ->when($request->sort === 'newest', fn ($q) => $q->orderByDesc('published_at'))
            ->when($request->sort === 'price_asc', fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) ASC'))
            ->when($request->sort === 'price_desc', fn ($q) => $q->orderByRaw('COALESCE(sale_price, price) DESC'))
            ->when(! $request->sort, fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('published_at'))
            ->paginate(12)
            ->withQueryString();

        $categories = CourseCategory::active()->ordered()->get(['id', 'name', 'slug', 'icon', 'color']);

        return Inertia::render('Lms/CourseCatalogue', [
            'courses' => $courses,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'level', 'sort', 'free']),
        ]);
    }

    public function show(Course $course): Response
    {
        abort_unless($course->status === 'published', 404);

        $course->load([
            'instructor:id,name',
            'category:id,name,slug,icon,color',
            'sections' => fn ($q) => $q->orderBy('sort_order')->with([
                'lessons' => fn ($q) => $q->orderBy('sort_order')->select([
                    'id', 'course_id', 'course_section_id', 'title', 'type',
                    'duration', 'is_free', 'description', 'sort_order',
                ]),
            ]),
        ]);

        $previewLessons = CourseLesson::query()
            ->where('course_id', $course->id)
            ->where('is_free', true)
            ->orderBy('sort_order')
            ->get(['id', 'course_section_id', 'title', 'type']);

        $isEnrolled = $course->grantsAccessTo(auth()->user());

        $requiresCheckout = auth()->check() && ! $isEnrolled && $course->effectivePrice() > 0;

        $courseNotes = collect();
        if ($isEnrolled) {
            $courseNotes = LessonNote::query()
                ->where('lesson_notes.user_id', auth()->id())
                ->whereHas('lesson', fn ($lessonQuery) => $lessonQuery->where('course_id', $course->id))
                ->join('course_lessons', 'course_lessons.id', '=', 'lesson_notes.course_lesson_id')
                ->join('course_sections', 'course_sections.id', '=', 'course_lessons.course_section_id')
                ->orderBy('course_sections.sort_order')
                ->orderBy('course_lessons.sort_order')
                ->orderBy('lesson_notes.created_at')
                ->select('lesson_notes.*')
                ->with([
                    'lesson:id,course_section_id,title,sort_order',
                    'lesson.section:id,title,sort_order',
                ])
                ->get()
                ->map(fn (LessonNote $note) => [
                    'id' => (int) $note->id,
                    'content' => $note->content,
                    'timestamp' => $note->timestamp,
                    'timestamp_seconds' => $note->timestamp_seconds !== null ? (int) $note->timestamp_seconds : null,
                    'lesson' => [
                        'id' => (int) $note->lesson->id,
                        'title' => $note->lesson->title,
                        'section_title' => $note->lesson->section?->title,
                    ],
                    'updated_at' => optional($note->updated_at)->toIso8601String(),
                ])
                ->values();
        }

        return Inertia::render('Lms/CourseShow', [
            'course' => $course,
            'previewLessons' => $previewLessons,
            'isEnrolled' => $isEnrolled,
            'requiresCheckout' => $requiresCheckout,
            'courseNotes' => $courseNotes,
        ]);
    }

    public function preview(Course $course, CourseLesson $lesson): Response
    {
        $this->assertLessonInPublishedCourse($course, $lesson);
        abort_unless($lesson->is_free, 403);

        $lesson->load('section:id,title', 'attachments');

        return Inertia::render('Lms/LessonPreview', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'lesson' => [
                ...$lesson->only([
                    'id', 'title', 'type', 'description', 'content', 'duration',
                ]),
                'video_url' => $this->learnerFacingVideoUrl(
                    $lesson->video_url,
                    $lesson->youtube_video_id
                ),
                'attachments' => $lesson->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'file_url' => $a->file_url,
                    'file_type' => $a->file_type,
                    'file_size' => $a->file_size,
                    'formatted_file_size' => $a->formatted_file_size,
                ])->values(),
            ],
        ]);
    }

    public function lesson(Course $course, CourseLesson $lesson): Response|RedirectResponse
    {
        $this->assertLessonInPublishedCourse($course, $lesson);
        $this->authorize('viewLesson', [$course, $lesson]);

        $course->load([
            'sections' => fn ($q) => $q->orderBy('sort_order')->with([
                'lessons' => fn ($lq) => $lq->orderBy('sort_order')->select([
                    'id', 'course_id', 'course_section_id', 'title', 'type',
                    'duration', 'is_free', 'description', 'content', 'video_url', 'sort_order',
                ]),
            ]),
        ]);

        $lesson->load('section:id,title', 'quiz:id,lesson_id', 'attachments:id,course_lesson_id,name,file_url,file_type,file_size,sort_order');

        if ($lesson->type === 'quiz' && $lesson->quiz !== null) {
            return redirect()->route('lms.quizzes.take', $lesson->quiz);
        }

        $completedLessonIds = CourseProgress::completedLessonIds((int) $course->id, (int) auth()->id());
        $progressPercent = CourseProgress::percent((int) $course->id, (int) auth()->id());

        $currentVideoProgress = LessonVideoProgress::query()
            ->where('user_id', auth()->id())
            ->where('course_lesson_id', $lesson->id)
            ->first(['course_lesson_id', 'watched_seconds', 'duration_seconds', 'percentage']);

        $discussionPayload = LessonDiscussionController::discussionPayloadForLesson(
            $course,
            $lesson,
            auth()->user()
        );

        $lessonNotes = LessonNote::query()
            ->where('user_id', auth()->id())
            ->where('course_lesson_id', $lesson->id)
            ->orderByRaw('timestamp_seconds IS NULL DESC')
            ->orderBy('timestamp_seconds')
            ->orderBy('created_at')
            ->get()
            ->map(fn (LessonNote $note) => [
                'id' => (int) $note->id,
                'content' => $note->content,
                'timestamp' => $note->timestamp,
                'timestamp_seconds' => $note->timestamp_seconds !== null ? (int) $note->timestamp_seconds : null,
                'updated_at' => optional($note->updated_at)->toIso8601String(),
            ])
            ->values();

        return Inertia::render('Lms/CoursePlayer', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'sections' => $course->sections->map(fn ($section) => [
                    'id' => $section->id,
                    'title' => $section->title,
                    'lessons' => $section->lessons->map(fn ($lsn) => [
                        'id' => $lsn->id,
                        'title' => $lsn->title,
                        'type' => $lsn->type,
                        'duration' => $lsn->duration,
                        'is_free' => (bool) $lsn->is_free,
                    ])->values(),
                ])->values(),
            ],
            'lesson' => [
                ...$lesson->only([
                    'id', 'title', 'type', 'description', 'content', 'duration', 'is_free',
                ]),
                'video_url' => $this->learnerFacingVideoUrl(
                    $lesson->video_url,
                    $lesson->youtube_video_id
                ),
                'attachments' => $lesson->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'file_url' => $a->file_url,
                    'file_type' => $a->file_type,
                    'file_size' => $a->file_size,
                    'formatted_file_size' => $a->formatted_file_size,
                ])->values(),
                'quiz_id' => $lesson->quiz?->id,
            ],
            'completedLessonIds' => $completedLessonIds,
            'videoProgress' => $currentVideoProgress ? [
                'watched_seconds' => (int) $currentVideoProgress->watched_seconds,
                'duration_seconds' => (int) $currentVideoProgress->duration_seconds,
                'percentage' => (float) $currentVideoProgress->percentage,
            ] : null,
            'progressPercent' => $progressPercent,
            'lessonDiscussions' => $discussionPayload,
            'lessonNotes' => $lessonNotes,
        ]);
    }

    private function learnerFacingVideoUrl(?string $videoUrl, ?string $youtubeVideoId = null): ?string
    {
        if (! $videoUrl) {
            return null;
        }

        $id = $youtubeVideoId ?: $this->extractYouTubeVideoId($videoUrl);
        if (! $id) {
            return $videoUrl;
        }

        return sprintf('https://www.youtube-nocookie.com/embed/%s', $id);
    }

    private function extractYouTubeVideoId(string $url): ?string
    {
        $trimmed = trim($url);

        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $trimmed, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
    }
}
