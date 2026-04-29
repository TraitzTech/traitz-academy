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

class CoursePaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesLmsNotificationPreferences;

    public function __construct(
        public Course $course,
        public ?string $reason = null
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
            LmsNotificationPreference::PAYMENT_FAILED,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $checkoutUrl = route('lms.courses.checkout', $this->course);

        $mail = (new MailMessage)
            ->subject('Payment could not be processed — '.$this->course->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('We could not process your payment for **'.$this->course->title.'**.');

        if ($this->reason) {
            $mail->line('Details: '.$this->reason);
        }

        return $mail
            ->line('What you can do:')
            ->lines([
                'Check that your mobile money wallet has sufficient balance.',
                'Confirm the phone number you entered matches your wallet.',
                'Try again from checkout, or contact support if the issue persists.',
            ])
            ->action('Return to checkout', $checkoutUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_payment_failed',
            'title' => 'Payment failed',
            'body' => $this->course->title,
            'url' => '/dashboard/courses/'.$this->course->id.'/checkout',
            'course_id' => $this->course->id,
        ];
    }
}
