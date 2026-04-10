<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\LessonVideoProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseCatalogueController extends Controller
{
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

        $isEnrolled = auth()->check()
            && Enrollment::query()
                ->where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->whereNotIn('access_status', ['suspended', 'revoked'])
                ->exists();

        $requiresCheckout = auth()->check() && ! $isEnrolled && $course->effectivePrice() > 0;

        return Inertia::render('Lms/CourseShow', [
            'course' => $course,
            'previewLessons' => $previewLessons,
            'isEnrolled' => $isEnrolled,
            'requiresCheckout' => $requiresCheckout,
        ]);
    }

    public function preview(Course $course, CourseLesson $lesson): Response
    {
        abort_unless($course->status === 'published', 404);
        abort_unless($lesson->course_id === $course->id, 404);
        abort_unless($lesson->is_free, 403);

        $lesson->load('section:id,title');

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
            ],
        ]);
    }

    public function lesson(Course $course, CourseLesson $lesson): Response|RedirectResponse
    {
        abort_unless($course->status === 'published', 404);
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);

        $enrollment = Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $course->id)
            ->whereNotIn('access_status', ['suspended', 'revoked'])
            ->first();

        $hasAccess = $lesson->is_free || $enrollment !== null;

        abort_unless($hasAccess, 403);

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

        $lessonIds = $course->sections
            ->flatMap(fn ($section) => $section->lessons->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->values();

        $completedFromMarks = LessonCompletion::query()
            ->where('user_id', auth()->id())
            ->whereIn('course_lesson_id', $lessonIds)
            ->pluck('course_lesson_id')
            ->map(fn ($id) => (int) $id);

        $videoProgressRows = LessonVideoProgress::query()
            ->where('user_id', auth()->id())
            ->whereIn('course_lesson_id', $lessonIds)
            ->get(['course_lesson_id', 'watched_seconds', 'duration_seconds', 'percentage']);

        $completedFromVideo = $videoProgressRows
            ->filter(fn ($row) => (float) $row->percentage >= LessonVideoProgress::COMPLETION_PERCENT_THRESHOLD)
            ->pluck('course_lesson_id')
            ->map(fn ($id) => (int) $id);

        $completedLessonIds = $completedFromMarks
            ->merge($completedFromVideo)
            ->unique()
            ->values();

        $currentVideoProgress = $videoProgressRows
            ->firstWhere('course_lesson_id', $lesson->id);

        $totalLessons = max(1, (int) $lessonIds->count());
        $progressPercent = (int) min(100, round(($completedLessonIds->count() / $totalLessons) * 100));

        $discussionPayload = LessonDiscussionController::discussionPayloadForLesson(
            $course,
            $lesson,
            auth()->user()
        );

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
