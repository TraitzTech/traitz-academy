<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $tutor = $request->user();

        // Core stats
        $totalCourses = Course::where('instructor_id', $tutor->id)->count();
        $activeCourses = Course::where('instructor_id', $tutor->id)->where('status', 'published')->count();
        $pendingCourses = Course::where('instructor_id', $tutor->id)->where('status', 'pending_review')->count();
        $totalStudents = Enrollment::countDistinctUsersForInstructor($tutor->id);

        // Recent enrollments (last 6)
        $recentEnrollments = Enrollment::with(['user:id,name,email', 'course:id,title,cover_image'])
            ->whereHas('course', fn ($q) => $q->where('instructor_id', $tutor->id))
            ->latest()
            ->take(6)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'student' => $e->user?->name,
                'course' => $e->course?->title,
                'cover_image' => $e->course?->cover_image,
                'enrolled_at' => $e->created_at?->diffForHumans(),
                'status' => $e->status,
            ]);

        // My courses overview (last 5)
        $myCourses = Course::where('instructor_id', $tutor->id)
            ->withCount('enrollments')
            ->with('category:id,name,color,icon')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'status' => $c->status,
                'cover_image' => $c->cover_image,
                'enrollments_count' => $c->enrollments_count,
                'level' => $c->level,
                'category' => $c->category ? ['name' => $c->category->name, 'color' => $c->category->color] : null,
                'price' => $c->price,
            ]);

        return Inertia::render('Tutor/Dashboard', [
            'stats' => [
                'total_students' => $totalStudents,
                'active_courses' => $activeCourses,
                'pending_courses' => $pendingCourses,
                'total_courses' => $totalCourses,
            ],
            'recentEnrollments' => $recentEnrollments,
            'myCourses' => $myCourses,
        ]);
    }
}
