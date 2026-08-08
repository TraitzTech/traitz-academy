<?php

namespace App\Notifications\Internships;

use App\Models\Internship;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LogbookReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Internship $internship) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Don't forget today's logbook entry")
            ->greeting("Hi {$notifiable->name},")
            ->line("You haven't submitted your internship logbook entry for today yet.")
            ->line('A short entry every working day keeps your supervisor in the loop on what you worked on — even on days you\'re not at the office.')
            ->action('Fill in today\'s logbook', route('internship.dashboard'))
            ->line('Thanks for keeping your log up to date!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'logbook_reminder',
            'internship_id' => $this->internship->id,
            'date' => now($this->internship->timezone())->toDateString(),
        ];
    }
}
