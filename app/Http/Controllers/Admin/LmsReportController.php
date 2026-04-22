<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LmsReportController extends Controller
{
    public function platformSummary(): Response
    {
        return Inertia::render('Admin/Lms/PlatformSummary', [
            'stats' => [
                'total_registered_users' => User::query()->count(),
                'total_published_courses' => Course::query()->where('status', 'published')->count(),
                'total_enrollments' => Enrollment::query()->where('access_status', '!=', 'revoked')->count(),
                'total_completions' => Enrollment::query()->where('access_status', 'completed')->count(),
                'total_certificates_issued' => Certificate::query()->count(),
                'total_reviews_submitted' => (int) Course::query()->sum('review_count'),
            ],
        ]);
    }

    public function courseReports(Request $request): Response|StreamedResponse
    {
        $search = trim((string) $request->input('search', ''));
        $instructor = trim((string) $request->input('instructor', ''));

        $courses = Course::query()
            ->with('instructor:id,name')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.$search.'%'))
            ->when($instructor !== '', fn ($q) => $q->whereHas('instructor', fn ($iq) => $iq->where('name', 'like', '%'.$instructor.'%')))
            ->withCount([
                'enrollments as total_enrollments' => fn ($q) => $q->where('access_status', '!=', 'revoked'),
                'enrollments as total_completions' => fn ($q) => $q->where('access_status', 'completed'),
            ])
            ->withSum(['coursePayments as revenue_collected' => fn ($q) => $q->where('status', 'successful')], 'amount')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'instructor_name' => $course->instructor?->name,
                'average_rating' => (float) ($course->rating ?? 0),
                'review_count' => (int) ($course->review_count ?? 0),
                'total_enrollments' => (int) ($course->total_enrollments ?? 0),
                'total_completions' => (int) ($course->total_completions ?? 0),
                'revenue_collected' => (float) ($course->revenue_collected ?? 0),
                'learners' => Enrollment::query()
                    ->where('course_id', $course->id)
                    ->where('access_status', '!=', 'revoked')
                    ->with('user:id,name,email')
                    ->latest('enrolled_at')
                    ->get()
                    ->map(fn (Enrollment $enrollment) => [
                        'id' => $enrollment->id,
                        'name' => $enrollment->user?->name,
                        'email' => $enrollment->user?->email,
                        'status' => $enrollment->access_status,
                        'progress' => (int) $enrollment->progress,
                    ])
                    ->values(),
            ])
            ->values();

        if ($request->input('export') === 'csv') {
            return response()->streamDownload(function () use ($courses) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Course', 'Tutor', 'Revenue', 'Average Rating', 'Review Count', 'Enrollments', 'Completions']);
                foreach ($courses as $course) {
                    fputcsv($handle, [
                        $course['title'],
                        $course['instructor_name'],
                        $course['revenue_collected'],
                        $course['average_rating'],
                        $course['review_count'],
                        $course['total_enrollments'],
                        $course['total_completions'],
                    ]);
                }
                fclose($handle);
            }, 'lms-course-reports-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
        }

        return Inertia::render('Admin/Lms/CourseReports', [
            'courses' => $courses,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'instructor' => $instructor !== '' ? $instructor : null,
            ],
        ]);
    }

    public function userReports(Request $request): Response|StreamedResponse
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->withCount([
                'enrollments as total_enrollments' => fn ($q) => $q->where('access_status', '!=', 'revoked'),
                'enrollments as total_completions' => fn ($q) => $q->where('access_status', 'completed'),
            ])
            ->withSum(['coursePayments as total_paid' => fn ($q) => $q->where('status', 'successful')], 'amount')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (User $user) {
                $learningRecords = Enrollment::query()
                    ->where('user_id', $user->id)
                    ->when(in_array($status, ['active', 'completed', 'suspended', 'revoked'], true), fn ($q) => $q->where('access_status', $status))
                    ->with('course:id,title')
                    ->latest('enrolled_at')
                    ->get()
                    ->map(fn (Enrollment $enrollment) => [
                        'id' => $enrollment->id,
                        'course_title' => $enrollment->course?->title,
                        'status' => $enrollment->access_status,
                        'progress' => (int) $enrollment->progress,
                        'enrolled_at' => optional($enrollment->enrolled_at)?->toIso8601String(),
                        'completed_at' => optional($enrollment->completed_at)?->toIso8601String(),
                    ])
                    ->values();

                $paymentRecords = CoursePayment::query()
                    ->where('user_id', $user->id)
                    ->with('course:id,title')
                    ->latest('paid_at')
                    ->get()
                    ->map(fn (CoursePayment $payment) => [
                        'id' => $payment->id,
                        'course_title' => $payment->course?->title,
                        'amount' => (float) $payment->amount,
                        'status' => $payment->status,
                        'payment_type' => $payment->payment_type,
                        'paid_at' => optional($payment->paid_at)?->toIso8601String(),
                    ])
                    ->values();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_enrollments' => (int) ($user->total_enrollments ?? 0),
                    'total_completions' => (int) ($user->total_completions ?? 0),
                    'total_paid' => (float) ($user->total_paid ?? 0),
                    'learning_records' => $learningRecords,
                    'payment_records' => $paymentRecords,
                ];
            })
            ->values();

        if ($request->input('export') === 'csv') {
            return response()->streamDownload(function () use ($users) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['User', 'Email', 'Total Enrollments', 'Total Completions', 'Total Paid']);
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user['name'],
                        $user['email'],
                        $user['total_enrollments'],
                        $user['total_completions'],
                        $user['total_paid'],
                    ]);
                }
                fclose($handle);
            }, 'lms-user-reports-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
        }

        return Inertia::render('Admin/Lms/UserReports', [
            'users' => $users,
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'status' => $status !== '' ? $status : null,
            ],
        ]);
    }
}
