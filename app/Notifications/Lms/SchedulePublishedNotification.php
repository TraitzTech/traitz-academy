<?php

namespace App\Notifications\Lms;

use App\Models\LmsSchedule;
use App\Models\User;
use App\Notifications\Concerns\UsesLmsNotificationPreferences;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SchedulePublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesLmsNotificationPreferences;

    public function __construct(
        public LmsSchedule $schedule
    ) {}

    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        return $this->channelsFor(
            $notifiable,
            LmsNotificationPreference::SCHEDULE_UPDATES,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $startsAt = $this->schedule->starts_at?->format('M d, Y H:i') ?? 'TBD';

        return (new MailMessage)
            ->subject('New schedule: '.$this->schedule->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('A new LMS schedule item has been published for you.')
            ->line('Starts at: '.$startsAt)
            ->action('Open my schedule', '/dashboard/schedules');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'schedule_published',
            'title' => 'New schedule item',
            'body' => $this->schedule->title,
            'url' => '/dashboard/schedules',
            'schedule_id' => $this->schedule->id,
            'starts_at' => $this->schedule->starts_at?->toIso8601String(),
        ];
    }
}
