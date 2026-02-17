<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $paymentSummary
     */
    public function __construct(private Application $application, private array $paymentSummary) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $programTitle = (string) ($this->application->program?->title ?? 'your program');
        $remainingAmount = number_format((float) ($this->paymentSummary['remaining_amount'] ?? 0), 0, '.', ',').' XAF';
        $installmentProgress = (int) ($this->paymentSummary['completed_installments'] ?? 0).' / '.(int) ($this->paymentSummary['max_installments'] ?? 1);
        $latestSuccessfulPayment = Payment::query()
            ->where('application_id', $this->application->id)
            ->where('status', 'successful')
            ->whereNotNull('receipt_number')
            ->latest('paid_at')
            ->latest('id')
            ->first();

        $message = (new MailMessage)
            ->subject('Payment Reminder for '.$programTitle)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('This is a friendly reminder to complete your payment for '.$programTitle.'.')
            ->line('Remaining balance: '.$remainingAmount)
            ->line('Installments paid: '.$installmentProgress)
            ->action('Complete Payment', route('payments.checkout', $this->application))
            ->line('If you have already paid, you can ignore this message.');

        if ($latestSuccessfulPayment instanceof Payment) {
            $message->line('Download your latest receipt: '.route('payments.receipt.download', $latestSuccessfulPayment));
        }

        return $message->salutation('Regards, '.$siteName);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'remaining_amount' => $this->paymentSummary['remaining_amount'] ?? 0,
            'completed_installments' => $this->paymentSummary['completed_installments'] ?? 0,
            'max_installments' => $this->paymentSummary['max_installments'] ?? 1,
        ];
    }
}
