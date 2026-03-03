<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\AiForgeRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AiForgeEventReminder extends Notification
{
    use Queueable;

    public function __construct(private AiForgeRegistration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $event = $this->registration->event;

        return (new MailMessage)
            ->subject("AI Forge Reminder - {$siteName}")
            ->greeting("Hello {$this->registration->first_name},")
            ->line('This is a friendly reminder about **AI Forge**!')
            ->line("**{$event->tagline}**")
            ->line('Event Details:')
            ->line('- **Dates:** '.optional($event->start_date)->format('F j, Y').' - '.optional($event->end_date)->format('F j, Y'))
            ->when($event->location, function (MailMessage $message) use ($event) {
                return $message->line("- **Location:** {$event->location}");
            })
            ->when($event->is_online && $event->event_url, function (MailMessage $message) use ($event) {
                return $message->line("- **Online Link:** {$event->event_url}");
            })
            ->line('')
            ->line("Don't forget to check out our exclusive AI Forge swag!")
            ->action('View AI Forge', url('/ai-forge'))
            ->line('See you there!')
            ->salutation("Best regards,\n{$siteName}");
    }
}
