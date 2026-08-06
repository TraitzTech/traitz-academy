<?php

namespace App\Notifications\Lms;

use App\Models\CoursePayment;
use App\Models\User;
use App\Notifications\Concerns\UsesLmsNotificationPreferences;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoursePaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesLmsNotificationPreferences;

    public function __construct(
        public CoursePayment $coursePayment
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
            LmsNotificationPreference::PAYMENT_RECEIVED,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->coursePayment->loadMissing('course:id,title');

        $receipt = $this->coursePayment->receipt_number ?? $this->coursePayment->reference;
        $amount = number_format((float) $this->coursePayment->amount, 2);
        $currency = $this->coursePayment->currency;
        $receiptUrl = route('lms.course-payments.receipt', $this->coursePayment);

        return (new MailMessage)
            ->subject('Payment received — '.$this->coursePayment->course?->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('We received your payment of **'.$amount.' '.$currency.'** for **'.$this->coursePayment->course?->title.'**.')
            ->line('Receipt reference: **'.$receipt.'**.')
            ->action('View receipt', $receiptUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->coursePayment->loadMissing('course:id,title');

        return [
            'type' => 'course_payment_received',
            'title' => 'Payment received',
            'body' => ($this->coursePayment->course?->title ?? 'Course').' — '.($this->coursePayment->receipt_number ?? $this->coursePayment->reference),
            'url' => '/dashboard/course-payments/'.$this->coursePayment->id,
            'course_payment_id' => $this->coursePayment->id,
            'course_id' => $this->coursePayment->course_id,
        ];
    }
}
