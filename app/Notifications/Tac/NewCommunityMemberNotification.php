<?php

namespace App\Notifications\Tac;

use App\Models\CommunityMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Admin-side heads-up when somebody joins TAC through the public Join form.
 * Auto-included registrants do not trigger this — those already have their own
 * registration notifications.
 */
class NewCommunityMemberNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private CommunityMember $member) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tracks = $this->member->tracks()->pluck('name');

        $message = (new MailMessage)
            ->subject('New TAC member: '.$this->member->full_name)
            ->greeting('New community member')
            ->line("**{$this->member->full_name}** just joined the Traitz Academy Community.")
            ->line("- **Email:** {$this->member->email}");

        if ($this->member->phone) {
            $message->line("- **Phone:** {$this->member->phone}");
        }

        if ($this->member->school) {
            $message->line("- **School:** {$this->member->school}");
        }

        $message->line('- **Status:** '.$this->member->statusLabel());

        if ($tracks->isNotEmpty()) {
            $message->line('- **Tracks:** '.$tracks->implode(', '));
        }

        if ($this->member->heard_about) {
            $message->line("- **Heard about TAC via:** {$this->member->heard_about}");
        }

        return $message->action('View member', url("/admin/community/members/{$this->member->id}"));
    }
}
