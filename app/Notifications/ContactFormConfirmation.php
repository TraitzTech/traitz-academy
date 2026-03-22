<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactFormConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $senderName,
        public string $subject,
        public string $message,
    ) {}

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
        $siteUrl = config('app.url');
        $logoUrl = SettingHelper::get('site_logo');

        return (new MailMessage)
            ->subject("Thank You for Contacting Us - {$siteName}")
            ->greeting("Hello {$this->senderName},")
            ->line('Thank you for reaching out to us! We have received your message and our team will review it shortly.')
            ->line('**Your Message Details:**')
            ->line("- **Subject:** {$this->subject}")
            ->line('- **Message:**')
            ->line("\"{$this->message}\"")
            ->line('---')
            ->line("We typically respond within 24-48 hours during business days. If your matter is urgent, please don't hesitate to reach out via WhatsApp or phone.")
            ->action('Visit Our Website', $siteUrl)
            ->line("Thank you for your interest in {$siteName}!")
            ->salutation("Best regards,\nThe {$siteName} Team");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender_name' => $this->senderName,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
