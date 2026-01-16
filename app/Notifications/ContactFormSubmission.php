<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactFormSubmission extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $subject,
        public string $message,
    ) {
    }

    /**
     * Get the notification's delivery channels.
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
            ->subject("New Contact Form Submission: {$this->subject}")
            ->greeting("Hello,")
            ->line("You have received a new contact form submission from your website.")
            ->line("**Sender Details:**")
            ->line("- **Name:** {$this->senderName}")
            ->line("- **Email:** {$this->senderEmail}")
            ->line("- **Subject:** {$this->subject}")
            ->line("**Message:**")
            ->line($this->message)
            ->action('Reply to Sender', "mailto:{$this->senderEmail}")
            ->line("---")
            ->line("This is an automated notification from {$siteName}.");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'sender_name' => $this->senderName,
            'sender_email' => $this->senderEmail,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
