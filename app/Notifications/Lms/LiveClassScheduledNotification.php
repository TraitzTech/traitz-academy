<?php

namespace App\Notifications\Lms;

use App\Models\LiveClass;
use App\Models\User;
use App\Notifications\Concerns\UsesLmsNotificationPreferences;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LiveClassScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesLmsNotificationPreferences;

    public function __construct(
        public LiveClass $liveClass
    ) {}

    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        return $this->channelsFor(
            $notifiable,
            LmsNotificationPreference::NEW_COURSE_PUBLISHED,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New live class: '.$this->liveClass->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('A live class has been scheduled and you have access.')
            ->line('Starts at: '.$this->liveClass->start_time->format('M d, Y H:i'))
            ->action('Open live class', '/dashboard/live-classes/'.$this->liveClass->id);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'live_class_scheduled',
            'title' => 'New live class',
            'body' => $this->liveClass->title,
            'url' => '/dashboard/live-classes/'.$this->liveClass->id,
            'live_class_id' => $this->liveClass->id,
        ];
    }
}
