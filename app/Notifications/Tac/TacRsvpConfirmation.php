<?php

namespace App\Notifications\Tac;

use App\Helpers\SettingHelper;
use App\Models\TacActivityRsvp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TacRsvpConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private TacActivityRsvp $rsvp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $activity = $this->rsvp->activity;
        $member = $this->rsvp->member;

        $waitlisted = $this->rsvp->status === TacActivityRsvp::STATUS_WAITLISTED;

        $message = (new MailMessage)
            ->subject(($waitlisted ? 'Waitlisted for ' : 'You are registered for ').$activity->title)
            ->greeting("Hello {$member->first_name},");

        $message->line($waitlisted
            ? "You're on the waitlist for **{$activity->title}**. We'll email you the moment a place opens up."
            : "Your place at **{$activity->title}** is confirmed.");

        $message->line('**'.$activity->typeLabel().'**');

        if ($activity->starts_at) {
            $message->line('- **When:** '.$activity->starts_at->timezone($activity->timezone)->format('l, F j, Y \a\t g:i A'));
        }

        if ($activity->location) {
            $message->line('- **Where:** '.$activity->location);
        }

        if ($activity->location_type !== 'physical' && $activity->meeting_url) {
            $message->line('- **Join link:** '.$activity->meeting_url);
        }

        if ($activity->track) {
            $message->line('- **Track:** '.$activity->track->name);
        }

        if ($this->rsvp->payment_status === TacActivityRsvp::PAYMENT_PENDING) {
            $message->line('')
                ->line('**Payment pending.** Your place is held until payment clears. If you have already paid via Mobile Money, no action is needed — we will confirm shortly.');
        }

        return $message
            ->action('View activity details', url("/community/activities/{$activity->slug}"))
            ->line('Need to cancel? You can withdraw your RSVP from your member area at any time.')
            ->salutation("See you there,\nThe {$siteName} team");
    }
}
