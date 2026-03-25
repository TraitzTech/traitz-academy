<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
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

        return Inertia::render('Lms/CourseCatalogue', [
            'courses'    => $courses,
            'categories' => $categories,
            'filters'    => $request->only(['search', 'category', 'level', 'sort']),
        ]);
    }
}
