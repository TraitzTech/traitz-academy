<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = Course::query()
            ->with('instructor:id,name', 'category:id,name,slug')
            ->withCount('enrollments')
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Courses/Index', [
            'courses'    => $courses,
            'categories' => CourseCategory::active()->ordered()->get(['id', 'name', 'slug']),
            'filters'    => $request->only(['search', 'status', 'category']),
        ]);
    }

    public function approve(Course $course): RedirectResponse
    {
        if ($course->status !== 'pending_review') {
            return back()->with('error', 'Only courses pending review can be approved.');
        }

        $course->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', "Course \"{$course->title}\" approved and published.");
    }

    public function reject(Request $request, Course $course): RedirectResponse
    {
        if ($course->status !== 'pending_review') {
            return back()->with('error', 'Only courses pending review can be rejected.');
        }

        $course->update(['status' => 'draft']);

        return back()->with('success', "Course \"{$course->title}\" returned to the tutor as a draft.");
    }

    public function archive(Course $course): RedirectResponse
    {
        $course->update(['status' => 'archived']);

        return back()->with('success', "Course \"{$course->title}\" archived.");
    }
}
