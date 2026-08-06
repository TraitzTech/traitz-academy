<?php

namespace App\Notifications\Lms;

use App\Models\Course;
use App\Models\User;
use App\Notifications\Concerns\UsesLmsNotificationPreferences;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CourseCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesLmsNotificationPreferences;

    public function __construct(
        public Course $course
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        return $this->channelsFor(
            $notifiable,
            LmsNotificationPreference::COURSE_COMPLETION,
            includeMail: false,
            includeDatabase: true
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_completed',
            'title' => 'Course completed',
            'body' => 'You finished '.$this->course->title.'. Your certificate is being generated.',
            'url' => '/dashboard/my-courses',
            'course_id' => $this->course->id,
        ];
    }
}
