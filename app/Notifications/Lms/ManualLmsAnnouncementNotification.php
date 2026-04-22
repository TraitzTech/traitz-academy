<?php

namespace App\Notifications\Lms;

use App\Helpers\SettingHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManualLmsAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $subject,
        public readonly string $messageHtml,
        public readonly ?string $actionText = null,
        public readonly ?string $actionUrl = null,
        public readonly ?string $senderName = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

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
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'lms_manual_announcement',
            'subject' => $this->subject,
            'message_html' => $this->messageHtml,
            'action_text' => $this->actionText,
            'action_url' => $this->actionUrl,
            'sender_name' => $this->senderName,
        ];
    }
}
