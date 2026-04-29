<?php

namespace App\Notifications\Lms;

use App\Models\Course;
use App\Models\User;
use App\Notifications\Concerns\UsesLmsNotificationPreferences;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseEnrollmentConfirmedNotification extends Notification implements ShouldQueue
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
            LmsNotificationPreference::ENROLMENT_CONFIRMATION,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('lms.courses.show', $this->course);

        return (new MailMessage)
            ->subject('You are enrolled in '.$this->course->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('Your enrolment in **'.$this->course->title.'** is confirmed.')
            ->action('Open course', $url)
            ->line('You can start learning right away from your course dashboard.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_enrolment_confirmed',
            'title' => 'Enrolment confirmed',
            'body' => $this->course->title,
            'url' => '/dashboard/courses/'.$this->course->id,
            'course_id' => $this->course->id,
        ];
    }
}
