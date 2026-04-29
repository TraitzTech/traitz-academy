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

class EnrollmentAccessSuspendedNotification extends Notification implements ShouldQueue
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
            LmsNotificationPreference::ACCESS_SUSPENDED,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $checkoutUrl = route('lms.courses.checkout', $this->course);

        return (new MailMessage)
            ->subject('Course access suspended — '.$this->course->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('Your access to **'.$this->course->title.'** has been suspended after repeated failed instalment payments.')
            ->line('To restore access, complete a successful payment from checkout. If you need help, contact our support team.')
            ->action('Go to checkout', $checkoutUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enrollment_access_suspended',
            'title' => 'Access suspended',
            'body' => $this->course->title,
            'url' => '/dashboard/courses/'.$this->course->id.'/checkout',
            'course_id' => $this->course->id,
        ];
    }
}
