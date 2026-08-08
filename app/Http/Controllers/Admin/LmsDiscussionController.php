<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LmsDiscussionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = [
            'search' => trim((string) $request->string('search')->value()),
            'status' => (string) $request->string('status')->value(),
        ];

        $query = Discussion::query()
            ->topLevel()
            ->with([
                'user:id,name',
                'lesson:id,course_id,title',
                'lesson.course:id,title,instructor_id',
                'lesson.course.instructor:id,name',
            ])
            ->withCount('replies')
            ->withExists([
                'replies as has_accepted_answer' => fn ($replyQuery) => $replyQuery->where('is_accepted_answer', true),
            ])
            ->latest('created_at');

        if ($filters['search'] !== '') {
            $searchTerm = $filters['search'];
            $query->where(function ($searchQuery) use ($searchTerm): void {
                $searchQuery
                    ->where('body', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$searchTerm}%"))
                    ->orWhereHas('lesson', fn ($lessonQuery) => $lessonQuery->where('title', 'like', "%{$searchTerm}%"))
                    ->orWhereHas('lesson.course', fn ($courseQuery) => $courseQuery->where('title', 'like', "%{$searchTerm}%"));
            });
        }

        if ($filters['status'] === 'accepted') {
            $query->whereHas('replies', fn ($replyQuery) => $replyQuery->where('is_accepted_answer', true));
        } elseif ($filters['status'] === 'answered') {
            $query->has('replies');
        } elseif ($filters['status'] === 'unanswered') {
            $query->doesntHave('replies');
        } elseif ($filters['status'] === 'mine') {
            $query->where('user_id', $user->id);
        }

        $discussions = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Discussion $discussion) => [
                'id' => $discussion->id,
                'body' => $discussion->body,
                'author_name' => $discussion->user?->name,
                'replies_count' => (int) $discussion->replies_count,
                'has_accepted_answer' => (bool) ($discussion->has_accepted_answer ?? false),
                'created_at' => optional($discussion->created_at)->toIso8601String(),
                'lesson' => [
                    'id' => $discussion->lesson?->id,
                    'title' => $discussion->lesson?->title,
                ],
                'course' => [
                    'id' => $discussion->lesson?->course?->id,
                    'title' => $discussion->lesson?->course?->title,
                    'tutor_name' => $discussion->lesson?->course?->instructor?->name,
                ],
                'destination_url' => $discussion->lesson && $discussion->lesson->course
                    ? route('admin.courses.show', ['course' => $discussion->lesson->course->id])
                    : null,
            ]);

        return Inertia::render('Admin/Lms/Discussions/Index', [
            'title' => 'LMS Discussions',
            'subtitle' => 'Monitor learner questions and tutor responses across all courses.',
            'filters' => $filters,
            'discussions' => $discussions,
        ]);
    }
}
