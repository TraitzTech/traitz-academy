<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private EventRegistration $registration) {}

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
        $event = $this->registration->event;
        $eventDate = optional($event->event_date)->format('l, F j, Y');
        $eventTime = optional($event->event_date)->format('g:i A');

        $message = (new MailMessage)
            ->subject("Reminder: {$event->title} is Coming Up! - {$siteName}")
            ->greeting("Hi {$this->registration->first_name}! 👋")
            ->line("This is a friendly reminder that **{$event->title}** is coming up soon!")
            ->line('')
            ->line('📅 **Event Details:**')
            ->line("- **Date:** {$eventDate}")
            ->line("- **Time:** {$eventTime}");

        if ($event->is_online && $event->event_url) {
            $message->line('- **Format:** 🌐 Online')
                ->line("- **Link:** [{$event->event_url}]({$event->event_url})");
        } elseif ($event->location) {
            $message->line("- **Location:** 📍 {$event->location}");
        }

        if ($event->capacity) {
            $spotsLeft = $event->capacity - ($event->registered_count ?? 0);
            $message->line("- **Spots Remaining:** {$spotsLeft} of {$event->capacity}");
        }

        $message->line('')
            ->line("We're excited to have you join us! Make sure to mark your calendar so you don't miss it.")
            ->action('View Event Details', route('events.show', $event->slug))
            ->line('')
            ->line('See you there! 🎉')
            ->salutation("Best regards,\n{$siteName}");

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->registration->event_id,
            'registration_id' => $this->registration->id,
            'type' => 'event_reminder',
        ];
    }
}
