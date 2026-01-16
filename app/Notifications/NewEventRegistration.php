<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEventRegistration extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public EventRegistration $registration)
    {
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
            ->subject("New Event Registration: {$this->registration->name} - {$this->registration->event->title}")
            ->greeting('New Event Registration!')
            ->line("A new participant has registered for one of your events.")
            ->line('')
            ->line('**Registration Details:**')
            ->line("- **Event:** {$this->registration->event->title}")
            ->line("- **Name:** {$this->registration->name}")
            ->line("- **Email:** {$this->registration->email}")
            ->line("- **Phone:** {$this->registration->phone}")
            ->line("- **Company:** {$this->registration->company}")
            ->line("- **Registration Date:** " . $this->registration->created_at->format('F j, Y \a\t g:i A'))
            ->line("- **Event Date:** " . $this->registration->event->event_date->format('F j, Y \a\t g:i A'))
            ->line('')
            ->action('View Registration', route('admin.applications.index'))
            ->line('---')
            ->line("This is an automated notification from {$siteName}.");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'registration_id' => $this->registration->id,
            'event_id' => $this->registration->event_id,
            'registrant_name' => $this->registration->name,
            'registrant_email' => $this->registration->email,
        ];
    }
}
