<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Payment $payment) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject("Payment Receipt {$this->payment->receipt_number}")
            ->view('emails.payment-receipt', [
                'siteName' => $siteName,
                'payment' => $this->payment,
                'application' => $this->payment->application,
                'program' => $this->payment->program,
                'receiptUrl' => route('payments.receipt', $this->payment),
                'receiptDownloadUrl' => route('payments.receipt.download', $this->payment),
                'notifiableName' => $notifiable->name ?? $this->payment->application?->first_name,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'reference' => $this->payment->reference,
            'receipt_number' => $this->payment->receipt_number,
            'status' => $this->payment->status,
        ];
    }
}
