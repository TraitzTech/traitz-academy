<?php

namespace App\Notifications\Tac;

use App\Helpers\SettingHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A track/school-scoped announcement a TAC leader sends to their members.
 * Mail-only: community members are not all {@see \App\Models\User} accounts,
 * so there is no in-app notification surface to also populate here.
 */
class TacAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $subject,
        public readonly string $messageHtml,
        public readonly ?string $actionText = null,
        public readonly ?string $actionUrl = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->first_name ?? 'there';
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
}
