<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\AiForgeRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AiForgeRegistrationConfirmation extends Notification
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
            ->subject("AI Forge Registration Confirmed - {$siteName}")
            ->greeting("Hello {$this->registration->first_name},")
            ->line('Welcome to **AI Forge**! Your registration has been confirmed.')
            ->line("**{$event->tagline}**")
            ->line('Event Details:')
            ->line('- **Dates:** '.optional($event->start_date)->format('F j, Y').' - '.optional($event->end_date)->format('F j, Y'))
            ->when($event->location, function (MailMessage $message) use ($event) {
                return $message->line("- **Location:** {$event->location}");
            })
            ->line('')
            ->line('**What you get:**')
            ->line('- 4 months free Gemini Pro access')
            ->line('- Expert mentorship from AI practitioners')
            ->line('- AI Forge completion certificate')
            ->line('- Real-world AI project portfolio')
            ->line('')
            ->line('Check out our exclusive AI Forge swag in the swag store!')
            ->action('View AI Forge', url('/ai-forge'))
            ->line('We look forward to seeing you!')
            ->salutation("Best regards,\n{$siteName}");
    }
}
