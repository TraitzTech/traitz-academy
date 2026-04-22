<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use App\Notifications\Lms\NewCoursePublishedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = Course::query()
            ->with('instructor:id,name', 'category:id,name,slug,color,icon')
            ->withCount('enrollments', 'sections')
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->category, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'categories' => CourseCategory::active()->ordered()->get(['id', 'name', 'slug']),
            'filters' => $request->only(['search', 'status', 'category']),
            'stats' => [
                'total' => Course::count(),
                'published' => Course::where('status', 'published')->count(),
                'pending' => Course::where('status', 'pending_review')->count(),
                'students' => \App\Models\Enrollment::distinct('user_id')->count('user_id'),
            ],
        ]);
    }

    public function show(Course $course): Response
    {
        $course->load([
            'instructor:id,name,email',
            'category:id,name,slug,icon,color',
            'sections' => fn ($q) => $q->orderBy('sort_order')->with([
                'lessons' => fn ($q) => $q->orderBy('sort_order')->with('quiz:id,lesson_id'),
            ]),
        ]);

        $course->loadCount('enrollments', 'sections');

        return Inertia::render('Admin/Courses/Show', [
            'course' => $course,
            'can_manually_enroll' => auth()->user()?->canManuallyEnrollStudentsInCourse($course) ?? false,
        ]);
    }

    public function edit(Course $course): Response
    {
        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course->load('category:id,name,slug'),
            'categories' => CourseCategory::active()->ordered()->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        // Inertia / JSON may send "" for empty optional fields; normalize so nullable + exists rules work.
        $categoryId = $request->input('category_id');
        $request->merge([
            'category_id' => ($categoryId === '' || $categoryId === null) ? null : $categoryId,
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'short_description' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'duration' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:draft,pending_review,published,archived'],
        ]);

        $course->update($validated);

        return redirect()
            ->route('admin.courses.edit', $course)
            ->with('success', 'Course updated successfully.');
    }

    public function approve(Course $course): RedirectResponse
    {
        if ($course->status !== 'pending_review') {
            return back()->with('error', 'Only courses pending review can be approved.');
        }

        $course->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        User::query()->orderBy('id')->chunkById(200, function ($users) use ($course) {
            foreach ($users as $user) {
                $user->notify(new NewCoursePublishedNotification($course));
            }
        });

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

    public function destroy(Course $course): RedirectResponse
    {
        if ($course->cover_image) {
            Storage::disk('public')->delete($course->cover_image);
        }

        $course->delete();

        return back()->with('success', 'Course deleted successfully.');
    }
}
