<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $requestedCourseId = $request->filled('course') ? (int) $request->input('course') : null;
        $requestedTutorId = $request->filled('tutor') ? (int) $request->input('tutor') : null;
        $status = $request->input('status');

        $tutors = User::query()
            ->where('role', User::ROLE_TUTOR)
            ->orderBy('name')
            ->get(['id', 'name']);

        $courses = Course::query()
            ->with('instructor:id,name')
            ->orderBy('title')
            ->get(['id', 'title', 'instructor_id']);

        $courseId = $requestedCourseId;
        if ($courseId && ! $courses->pluck('id')->contains($courseId)) {
            $courseId = null;
        }

        $tutorId = $requestedTutorId;
        if ($tutorId && ! $tutors->pluck('id')->contains($tutorId)) {
            $tutorId = null;
        }

        $query = Enrollment::query()
            ->with([
                'user:id,name,email',
                'course:id,title,instructor_id',
                'course.instructor:id,name',
            ]);

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
        }

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        if ($tutorId) {
            $query->whereHas('course', fn ($q) => $q->where('instructor_id', $tutorId));
        }

        if (is_string($status) && in_array($status, ['active', 'suspended', 'revoked', 'completed'], true)) {
            $query->where('access_status', $status);
        }

        $enrollments = $query
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Enrollments/Index', [
            'enrollments' => $enrollments,
            'tutors' => $tutors,
            'courses' => $courses,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'course' => $courseId,
                'tutor' => $tutorId,
                'status' => is_string($status) ? $status : null,
            ],
        ]);
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'access_status' => ['required', 'in:active,suspended,revoked,completed'],
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $completedAt = $validated['access_status'] === 'completed'
            ? ($enrollment->completed_at ?? now())
            : null;

        $enrollment->update([
            'access_status' => $validated['access_status'],
            'progress' => $validated['progress'],
            'completed_at' => $completedAt,
        ]);

        return back()->with('success', 'Enrollment updated.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $enrollment->delete();

        return back()->with('success', 'Enrollment removed.');
    }
}
