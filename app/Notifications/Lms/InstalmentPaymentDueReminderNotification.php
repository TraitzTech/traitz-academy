<?php

namespace App\Notifications\Lms;

use App\Models\Course;
use App\Models\User;
use App\Notifications\Concerns\UsesLmsNotificationPreferences;
use App\Support\Lms\LmsNotificationPreference;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstalmentPaymentDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesLmsNotificationPreferences;

    public function __construct(
        public Course $course,
        public float $amountDue,
        public string $currency,
        public CarbonInterface $dueDate
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
            LmsNotificationPreference::INSTALMENT_REMINDER,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $checkoutUrl = route('lms.courses.checkout', $this->course);
        $amt = number_format($this->amountDue, 2);

        return (new MailMessage)
            ->subject('Upcoming instalment — '.$this->course->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('This is a reminder that an instalment of **'.$amt.' '.$this->currency.'** for **'.$this->course->title.'** is scheduled on **'.$this->dueDate->format('j F Y').'**.')
            ->action('Open checkout', $checkoutUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $amt = number_format($this->amountDue, 2);

        return [
            'type' => 'instalment_payment_due_reminder',
            'title' => 'Instalment due soon',
            'body' => $this->course->title.' — '.$amt.' '.$this->currency.' on '.$this->dueDate->format('j M Y'),
            'url' => '/dashboard/courses/'.$this->course->id.'/checkout',
            'course_id' => $this->course->id,
        ];
    }
}
