<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $subject,
        public string $messageHtml,
        public ?string $actionText = null,
        public ?string $actionUrl = null
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
        $name = $notifiable->name ?? 'there';
        $siteName = SettingHelper::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject($this->subject)
            ->view('emails.batch-notification', [
                'recipientName' => $name,
                'siteName' => $siteName,
                'subject' => $this->subject,
                'messageHtml' => $this->messageHtml,
                'actionText' => $this->actionText,
                'actionUrl' => $this->actionUrl,
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
            'subject' => $this->subject,
            'message_html' => $this->messageHtml,
        ];
    }
}
