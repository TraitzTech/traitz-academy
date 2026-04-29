<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $authUser = $request->user();

        $adminRoles = [
            User::ROLE_CTO,
            User::ROLE_CEO,
            User::ROLE_PROGRAM_COORDINATOR,
            User::ROLE_ADMIN_LEGACY,
        ];

        $totalCollectedQuery = Payment::query()->where('status', 'successful');

        if ($authUser->isProgramCoordinator()) {
            $totalCollectedQuery->where('manual_entry', true)
                ->where(function ($query) use ($authUser) {
                    $query->where('recorded_by', $authUser->id)
                        ->orWhere(function ($fallbackQuery) use ($authUser) {
                            $fallbackQuery->whereNull('recorded_by')
                                ->where('updated_by', $authUser->id);
                        });
                });
        }

        $stats = [
            'total_programs' => Program::count(),
            'total_events' => Event::count(),
            'pending_applications' => Application::where('status', 'pending')->count(),
            'total_users' => User::whereNotIn('role', $adminRoles)->count(),
            'total_collected' => (float) $totalCollectedQuery->sum('amount'),
            'collected_label' => $authUser->isProgramCoordinator() ? 'My Collected' : 'Total Collected',
            'pending_courses' => Course::where('status', 'pending_review')->count(),
            'lms_distinct_learners' => Enrollment::countDistinctNonRevokedUsers(),
            'lms_total_enrollments' => Enrollment::query()->where('access_status', '!=', 'revoked')->count(),
        ];

        $recentApplications = Application::with('program')
            ->latest()
            ->take(5)
            ->get();

        $pendingCourses = Course::where('status', 'pending_review')
            ->with('instructor:id,name', 'category:id,name,slug')
            ->withCount('sections', 'enrollments')
            ->latest()
            ->get();

        $recentLmsEnrollments = Enrollment::query()
            ->with(['user:id,name,email', 'course:id,title,instructor_id', 'course.instructor:id,name'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Enrollment $e) => [
                'id' => $e->id,
                'student_name' => $e->user?->name,
                'student_email' => $e->user?->email,
                'course_title' => $e->course?->title,
                'tutor_name' => $e->course?->instructor?->name,
                'access_status' => $e->access_status,
                'created_at' => $e->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentApplications' => $recentApplications,
            'pendingCourses' => $pendingCourses,
            'recentLmsEnrollments' => $recentLmsEnrollments,
        ]);
    }
}
