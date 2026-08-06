<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Discussion;
use App\Models\User;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class LessonDiscussionQuestionPosted extends Notification
{
    public function __construct(
        public Course $course,
        public CourseLesson $lesson,
        public Discussion $discussion
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User && ! LmsNotificationPreference::accepts($notifiable, LmsNotificationPreference::INSTRUCTOR_LESSON_QUESTIONS)) {
            return [];
        }

        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lesson_discussion_question',
            'title' => 'New question in '.$this->course->title,
            'body' => Str::limit(strip_tags($this->discussion->body), 160),
            'url' => '/dashboard/courses/'.$this->course->id.'/lessons/'.$this->lesson->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'discussion_id' => $this->discussion->id,
        ];
    }
}
