<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\Discussion;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class LessonDiscussionReplyPosted extends Notification
{
    public function __construct(
        public Course $course,
        public CourseLesson $lesson,
        public Discussion $reply,
        public Discussion $rootQuestion
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lesson_discussion_reply',
            'title' => 'New reply: '.$this->lesson->title,
            'body' => Str::limit(strip_tags($this->reply->body), 160),
            'url' => '/dashboard/courses/'.$this->course->id.'/lessons/'.$this->lesson->id,
            'course_id' => $this->course->id,
            'lesson_id' => $this->lesson->id,
            'discussion_id' => $this->reply->id,
            'root_discussion_id' => $this->rootQuestion->id,
        ];
    }
}
