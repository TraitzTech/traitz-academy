<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllCoursesController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = Course::query()
            ->published()
            ->with('instructor:id,name', 'category:id,name,slug,color')
            ->when($request->category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->when($request->level, fn ($q) => $q->where('level', $request->level))
            ->when($request->search, fn ($q) => $q->where(function ($builder) use ($request) {
                $builder->where('title', 'like', "%{$request->search}%")
                    ->orWhere('short_description', 'like', "%{$request->search}%");
            }))
            ->when($request->sort === 'popular', fn ($q) => $q->orderByDesc('enrolled_count'))
            ->when($request->sort === 'rating', fn ($q) => $q->orderByDesc('rating'))
            ->when($request->sort === 'newest', fn ($q) => $q->orderByDesc('published_at'))
            ->when(! $request->sort, fn ($q) => $q->orderByDesc('is_featured')->orderByDesc('published_at'))
            ->paginate(12)
            ->withQueryString();

        $categories = CourseCategory::active()->ordered()->get(['id', 'name', 'slug', 'icon', 'color']);

        return Inertia::render('Lms/AllCourses', [
            'courses' => $courses,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'level', 'sort']),
        ]);
    }

    public function show(Course $course): Response
    {
        abort_unless($course->status === 'published', 404);

        $course->load([
            'instructor:id,name',
            'category:id,name,slug,icon,color',
            'sections' => fn ($q) => $q->orderBy('sort_order')->with([
                'lessons' => fn ($lq) => $lq->orderBy('sort_order')->select([
                    'id',
                    'course_id',
                    'course_section_id',
                    'title',
                    'description',
                    'type',
                    'duration',
                    'is_free',
                    'sort_order',
                ]),
            ]),
        ]);

        $previewLessons = $course->lessons()
            ->free()
            ->orderBy('sort_order')
            ->get(['id', 'course_section_id', 'title', 'type']);

        $isEnrolled = Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $course->id)
            ->whereNotIn('access_status', ['suspended', 'revoked'])
            ->exists();

        $requiresCheckout = ! $isEnrolled && $course->effectivePrice() > 0;

        return Inertia::render('Lms/CourseDetail', [
            'course' => $course,
            'previewLessons' => $previewLessons,
            'isEnrolled' => $isEnrolled,
            'requiresCheckout' => $requiresCheckout,
        ]);
    }
}
