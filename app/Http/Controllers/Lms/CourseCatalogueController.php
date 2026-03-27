<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLesson;
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
            'instalmentPlans' => fn ($q) => $q->where('is_active', true)->orderBy('id'),
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

        return Inertia::render('Lms/CourseShow', [
            'course' => $course,
            'previewLessons' => $previewLessons,
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
            'lesson' => $lesson->only([
                'id', 'title', 'type', 'description', 'content', 'video_url', 'duration',
            ]),
        ]);
    }
}
