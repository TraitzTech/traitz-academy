<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FeedbackThankYouNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private \App\Models\FeedbackForm $form,
        private ?string $recipientName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = \App\Helpers\SettingHelper::get('site_name', config('app.name'));
        $name = $this->recipientName ?? $notifiable->name ?? 'there';

        return (new MailMessage)
            ->subject("Thanks for your feedback — {$this->form->title}")
            ->greeting("Hello {$name},")
            ->line("Thank you so much for taking the time to fill out our feedback form: **{$this->form->title}**.")
            ->line('Your input helps us continuously improve the Traitz Academy experience for every intern and student.')
            ->line('We appreciate you sharing your thoughts with us!')
            ->action('Visit Traitz Academy', url('/'))
            ->salutation("Warm regards,\n{$siteName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'feedback_form_id' => $this->form->id,
        ];
    }
}
