<?php

namespace App\Notifications\Tac;

use App\Helpers\SettingHelper;
use App\Models\TacActivityRsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TacActivityReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private TacActivityRsvp $rsvp,
        private ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $activity = $this->rsvp->activity;
        $member = $this->rsvp->member;

        $message = (new MailMessage)
            ->subject("Reminder: {$activity->title}")
            ->greeting("Hello {$member->first_name},")
            ->line("This is a reminder about **{$activity->title}**, which you registered for.");

        if ($activity->starts_at) {
            $starts = $activity->starts_at->timezone($activity->timezone);
            $message->line('- **When:** '.$starts->format('l, F j, Y \a\t g:i A').' ('.$starts->diffForHumans().')');
        }

        if ($activity->location) {
            $message->line('- **Where:** '.$activity->location);
        }

        if ($activity->location_type !== 'physical' && $activity->meeting_url) {
            $message->line('- **Join link:** '.$activity->meeting_url);
        }

        if ($this->note) {
            $message->line('')->line($this->note);
        }

        return $message
            ->action('View activity details', url("/community/activities/{$activity->slug}"))
            ->salutation("See you soon,\nThe {$siteName} team");
    }
}
