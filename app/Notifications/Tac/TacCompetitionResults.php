<?php

namespace App\Notifications\Tac;

use App\Helpers\SettingHelper;
use App\Models\TacCompetitionEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TacCompetitionResults extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private TacCompetitionEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $activity = $this->entry->activity;
        $member = $this->entry->member;

        $message = (new MailMessage)
            ->subject("Results are in: {$activity->title}")
            ->greeting("Hello {$member->first_name},");

        if ($this->entry->is_winner) {
            $message->line("**Congratulations!** Your entry “{$this->entry->title}” won ".($this->entry->award ?: 'the competition').'.');
        } elseif ($this->entry->rank !== null) {
            $message->line("Your entry “{$this->entry->title}” placed **#{$this->entry->rank}** in {$activity->title}.");
        } else {
            $message->line("The results for **{$activity->title}** have been published.");
        }

        if ($this->entry->total_score !== null) {
            $message->line('**Your score:** '.number_format((float) $this->entry->total_score, 1).' / 100');
        }

        if ($this->entry->judge_notes) {
            $message->line('')
                ->line('**Feedback from the judges:**')
                ->line($this->entry->judge_notes);
        }

        return $message
            ->line('')
            ->line('Thank you for entering — competitions like this are what keep the community sharp.')
            ->action('See the full leaderboard', url("/community/activities/{$activity->slug}"))
            ->salutation("Well done,\nThe {$siteName} team");
    }
}
