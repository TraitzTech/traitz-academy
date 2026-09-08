<?php

namespace App\Notifications\Tac;

use App\Helpers\SettingHelper;
use App\Models\CommunityMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when somebody deliberately joins TAC through the public Join form.
 */
class TacJoinConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private CommunityMember $member) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $tracks = $this->member->tracks()->pluck('name');

        $message = (new MailMessage)
            ->subject('Welcome to the Traitz Academy Community')
            ->greeting("Welcome, {$this->member->first_name}!")
            ->line('Your membership of the **Traitz Academy Community (TAC)** is confirmed. You are now part of a standing community of students, interns, mentors and tech enthusiasts.');

        if ($tracks->isNotEmpty()) {
            $message->line('**Your tracks:** '.$tracks->implode(', '));
        }

        return $message
            ->line('Here is what happens next:')
            ->line('- Watch the activities calendar for workshops, trainings, bootcamps and competitions')
            ->line('- Meet the mentors leading your track')
            ->line('- RSVP to anything that interests you — most activities are free')
            ->action('Go to the community', url('/community'))
            ->line('TAC runs all year, every year. Take your time and get involved at your own pace.')
            ->salutation("Glad to have you,\nThe {$siteName} team");
    }
}
