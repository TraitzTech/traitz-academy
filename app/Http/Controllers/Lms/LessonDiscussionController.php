<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Discussion;
use App\Models\DiscussionUpvote;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\LessonDiscussionQuestionPosted;
use App\Notifications\LessonDiscussionReplyPosted;
use App\Support\Lms\LessonDiscussionPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class LessonDiscussionController extends Controller
{
    public function store(Request $request, Course $course, CourseLesson $lesson): RedirectResponse
    {
        $this->assertPublishedCourseLesson($course, $lesson);
        $user = $request->user();
        abort_unless($user !== null, 403);
        $this->assertViewerCanAccessLesson($course, $lesson, $user);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'parent_id' => ['nullable', 'integer', 'exists:discussions,id'],
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId !== null) {
            $parent = Discussion::query()->findOrFail($parentId);
            abort_unless((int) $parent->lesson_id === (int) $lesson->id, 404);
            abort_unless($parent->isTopLevel(), 422);
        }

        $discussion = Discussion::query()->create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'parent_id' => $parentId,
            'body' => $validated['body'],
        ]);

        if ($parentId === null) {
            $instructor = $course->instructor;
            if ($instructor !== null && (int) $instructor->id !== (int) $user->id) {
                $instructor->notify(new LessonDiscussionQuestionPosted($course, $lesson, $discussion));
            }
        } else {
            $this->notifyReplyParticipants($course, $lesson, $discussion, (int) $parentId, $user);
        }

        return back()->with('success', $parentId === null ? 'Question posted.' : 'Reply posted.');
    }

    public function destroy(Request $request, Course $course, CourseLesson $lesson, Discussion $discussion): RedirectResponse
    {
        $this->assertPublishedCourseLesson($course, $lesson);
        $user = $request->user();
        abort_unless($user !== null, 403);
        abort_unless((int) $discussion->lesson_id === (int) $lesson->id, 404);

        $this->assertViewerCanAccessLesson($course, $lesson, $user);

        $canModerate = $this->canModerateCourse($user, $course);
        $isAuthor = (int) $discussion->user_id === (int) $user->id;
        abort_unless($isAuthor || $canModerate, 403);

        DB::transaction(function () use ($discussion): void {
            if ($discussion->isTopLevel()) {
                Discussion::query()->where('parent_id', $discussion->id)->delete();
            }
            $discussion->delete();
        });

        return back()->with('success', 'Post removed.');
    }

    public function toggleUpvote(Request $request, Course $course, CourseLesson $lesson, Discussion $discussion): RedirectResponse
    {
        $this->assertPublishedCourseLesson($course, $lesson);
        $user = $request->user();
        abort_unless($user !== null, 403);
        abort_unless((int) $discussion->lesson_id === (int) $lesson->id, 404);
        abort_unless($discussion->isTopLevel(), 422);

        $this->assertViewerCanAccessLesson($course, $lesson, $user);

        $existing = DiscussionUpvote::query()
            ->where('user_id', $user->id)
            ->where('discussion_id', $discussion->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $discussion->decrement('upvotes_count');
        } else {
            DiscussionUpvote::query()->create([
                'user_id' => $user->id,
                'discussion_id' => $discussion->id,
            ]);
            $discussion->increment('upvotes_count');
        }

        return back();
    }

    public function acceptAnswer(Request $request, Course $course, CourseLesson $lesson, Discussion $discussion): RedirectResponse
    {
        $this->assertPublishedCourseLesson($course, $lesson);
        $user = $request->user();
        abort_unless($user !== null, 403);
        abort_unless((int) $discussion->lesson_id === (int) $lesson->id, 404);
        abort_unless($discussion->isReply(), 422);

        $this->assertViewerCanAccessLesson($course, $lesson, $user);
        abort_unless($this->canModerateCourse($user, $course), 403);

        $rootId = (int) $discussion->parent_id;
        DB::transaction(function () use ($discussion, $rootId): void {
            Discussion::query()
                ->where('parent_id', $rootId)
                ->update(['is_accepted_answer' => false]);
            $discussion->update(['is_accepted_answer' => true]);
        });

        return back()->with('success', 'Accepted answer updated.');
    }

    private function notifyReplyParticipants(Course $course, CourseLesson $lesson, Discussion $reply, int $rootQuestionId, User $actor): void
    {
        $root = Discussion::query()->findOrFail($rootQuestionId);
        $recipients = collect();

        if ((int) $root->user_id !== (int) $actor->id) {
            $author = User::query()->find($root->user_id);
            if ($author) {
                $recipients->push($author);
            }
        }

        $upvoterIds = DiscussionUpvote::query()
            ->where('discussion_id', $rootQuestionId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id !== (int) $actor->id && $id !== (int) $root->user_id);

        if ($upvoterIds->isNotEmpty()) {
            $recipients = $recipients->merge(
                User::query()->whereIn('id', $upvoterIds->unique()->all())->get()
            );
        }

        $recipients = $recipients->unique('id')->values();
        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new LessonDiscussionReplyPosted($course, $lesson, $reply, $root)
        );
    }

    private function assertPublishedCourseLesson(Course $course, CourseLesson $lesson): void
    {
        abort_unless($course->status === 'published', 404);
        abort_unless((int) $lesson->course_id === (int) $course->id, 404);
    }

    private function assertViewerCanAccessLesson(Course $course, CourseLesson $lesson, User $user): void
    {
        if ($this->canModerateCourse($user, $course)) {
            return;
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->whereNotIn('access_status', ['suspended', 'revoked'])
            ->first();

        $hasAccess = $lesson->is_free || $enrollment !== null;
        abort_unless($hasAccess, 403);
    }

    private function canModerateCourse(User $user, Course $course): bool
    {
        if ($user->canAccessAdminPanel()) {
            return true;
        }

        return $user->isTutor() && (int) $course->instructor_id === (int) $user->id;
    }

    public static function discussionPayloadForLesson(Course $course, CourseLesson $lesson, User $user): array
    {
        $controller = new self;
        $canModerate = $controller->canModerateCourse($user, $course);

        return LessonDiscussionPresenter::forLesson($lesson, $user, $canModerate);
    }
}
