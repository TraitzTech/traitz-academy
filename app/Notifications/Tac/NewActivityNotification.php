<?php

namespace App\Notifications\Tac;

use App\Helpers\SettingHelper;
use App\Models\TacActivity;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to community members when a new activity is published in their
 * track (or to everyone, for a track-less activity). Deliberately not
 * ShouldQueue — it's dispatched one member at a time from
 * NotifyActivityMembersJob, which already runs on the queue and needs to
 * catch a per-send mailer failure itself to pace around a rate limit.
 */
class NewActivityNotification extends Notification
{
    public function __construct(private TacActivity $activity) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $activity = $this->activity;
        $typeLabel = mb_strtolower(TacActivity::TYPE_LABELS[$activity->type] ?? 'activity');

        $intro = $activity->track
            ? "A new {$typeLabel} has been added for the **{$activity->track->name}** track: **{$activity->title}**."
            : "A new {$typeLabel} has been added to the community calendar: **{$activity->title}**.";

        $message = (new MailMessage)
            ->subject("New on TAC: {$activity->title}")
            ->greeting("Hello {$notifiable->first_name},")
            ->line($intro);

        if ($activity->summary) {
            $message->line($activity->summary);
        }

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

        return $message
            ->action('View activity details', url("/community/activities/{$activity->slug}"))
            ->salutation("See you there,\nThe {$siteName} team");
    }
}
