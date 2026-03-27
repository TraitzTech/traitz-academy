<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $tutorId = auth()->id();

        $courses = Course::query()
            ->where('instructor_id', $tutorId)
            ->with('category:id,name,slug')
            ->withCount('enrollments')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Tutor/Courses/Index', [
            'courses' => $courses,
            'stats'   => [
                'total'    => Course::where('instructor_id', $tutorId)->count(),
                'active'   => Course::where('instructor_id', $tutorId)->where('status', 'published')->count(),
                'pending'  => Course::where('instructor_id', $tutorId)->where('status', 'pending_review')->count(),
                'students' => \App\Models\Enrollment::whereHas(
                    'course', fn ($q) => $q->where('instructor_id', $tutorId)
                )->distinct('user_id')->count('user_id'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tutor/Courses/Create', [
            'categories' => CourseCategory::active()->ordered()->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'category_id'       => ['nullable', 'exists:course_categories,id'],
            'level'             => ['required', 'in:beginner,intermediate,advanced'],
            'short_description' => ['required', 'string', 'max:500'],
        ]);

        $slug = $this->uniqueSlug($validated['title']);

        $course = Course::create([
            ...$validated,
            'instructor_id' => auth()->id(),
            'slug'          => $slug,
            'status'        => 'draft',
        ]);

        return redirect()->route('tutor.courses.edit', $course)
            ->with('success', 'Course created. Now add the details and curriculum.');
    }

    public function edit(Course $course): Response
    {
        $this->authorise($course);

        $course->load([
            'category:id,name,slug',
            'sections' => fn ($q) => $q->ordered()->with([
                'lessons' => fn ($q) => $q->ordered(),
            ]),
        ]);

        return Inertia::render('Tutor/Courses/Edit', [
            'course'     => $course,
            'categories' => CourseCategory::active()->ordered()->get(['id', 'name', 'slug']),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorise($course);

        $validated = $request->validate([
            'title'             => ['required', 'string', 'max:255'],
            'category_id'       => ['nullable', 'exists:course_categories,id'],
            'level'             => ['required', 'in:beginner,intermediate,advanced'],
            'short_description' => ['required', 'string', 'max:500'],
            'description'       => ['nullable', 'string'],
            'price'             => ['required', 'numeric', 'min:0'],
            'sale_price'        => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'duration'          => ['nullable', 'string', 'max:100'],
        ]);

        // Regenerate slug if title changed
        if ($validated['title'] !== $course->title) {
            $validated['slug'] = $this->uniqueSlug($validated['title'], $course->id);
        }

        $course->update($validated);

        return back()->with('success', 'Course details saved.');
    }

    public function uploadCover(Request $request, Course $course): RedirectResponse
    {
        $this->authorise($course);

        $request->validate([
            'cover_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        // Delete old cover if exists
        if ($course->cover_image) {
            Storage::disk('public')->delete($course->cover_image);
        }

        $path = $request->file('cover_image')->store('course-covers', 'public');
        $course->update(['cover_image' => $path]);

        return back()->with('success', 'Cover image updated.');
    }

    public function submit(Course $course): RedirectResponse
    {
        $this->authorise($course);

        if (! in_array($course->status, ['draft', 'archived'])) {
            return back()->with('error', 'This course cannot be submitted for review.');
        }

        if ($course->sections()->count() === 0) {
            return back()->with('error', 'Add at least one section before submitting for review.');
        }

        $course->update(['status' => 'pending_review']);

        return back()->with('success', 'Course submitted for review. An admin will review it shortly.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorise($course);

        if ($course->status === 'published') {
            return back()->with('error', 'Published courses cannot be deleted. Archive it first.');
        }

        if ($course->cover_image) {
            Storage::disk('public')->delete($course->cover_image);
        }

        $course->delete();

        return redirect()->route('tutor.courses.index')
            ->with('success', 'Course deleted.');
    }

    private function authorise(Course $course): void
    {
        if ($course->instructor_id !== auth()->id()) {
            abort(403, 'You do not have permission to manage this course.');
        }
    }

    private function uniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;

        while (
            Course::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
