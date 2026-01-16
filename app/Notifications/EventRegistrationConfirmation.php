<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRegistrationConfirmation extends Notification
{
    use Queueable;

    public function __construct(private EventRegistration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject("Event Registration Confirmed - {$siteName}")
            ->greeting("Hello {$this->registration->first_name},")
            ->line("Thank you for registering for **{$this->registration->event->title}**.")
            ->line('We have received your registration and saved your spot.')
            ->line('Event Details:')
            ->line("- Date: " . optional($this->registration->event->event_date)->format('F j, Y g:i A'))
            ->when($this->registration->event->is_online && $this->registration->event->event_url, function (MailMessage $message) {
                return $message->line("- Online Link: {$this->registration->event->event_url}");
            })
            ->when(!$this->registration->event->is_online && $this->registration->event->location, function (MailMessage $message) {
                return $message->line("- Location: {$this->registration->event->location}");
            })
            ->action('View Event', route('events.show', $this->registration->event->slug))
            ->line('We look forward to seeing you!')
            ->salutation("Best regards,\n{$siteName}");
    }
}
