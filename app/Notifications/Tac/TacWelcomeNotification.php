<?php

namespace App\Notifications\Tac;

use App\Helpers\SettingHelper;
use App\Models\CommunityMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when somebody is auto-included in TAC off the back of registering for
 * something else. It leads with *why* they are hearing from us, so the email
 * never reads as unsolicited.
 */
class TacWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private CommunityMember $member,
        private ?string $context = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $reason = $this->reasonLine();

        $message = (new MailMessage)
            ->subject("You're now part of the Traitz Academy Community")
            ->greeting("Hello {$this->member->first_name},")
            ->line($reason)
            ->line('**The Traitz Academy Community (TAC)** is our year-round home base for students, interns and tech enthusiasts — it keeps running between and beyond internship cohorts.')
            ->line('As a member you get:')
            ->line('- Workshops, trainings, bootcamps and competitions all year round')
            ->line('- A track of your choice, with mentors who work in it')
            ->line('- A path to grow into a mentor or lead yourself')
            ->line('- First word on new programs, internships and opportunities')
            ->action('Explore the community', url('/community'));

        if ($whatsapp = SettingHelper::communityWhatsAppInvite()) {
            $message->line("Join the community on WhatsApp too: {$whatsapp}");
        }

        return $message
            ->line('Pick your tracks and complete your profile so we can point the right opportunities your way.')
            ->salutation("See you inside,\nThe {$siteName} team");
    }

    private function reasonLine(): string
    {
        $source = CommunityMember::SOURCE_LABELS[$this->member->source] ?? null;

        if ($this->context) {
            return "Because you registered for **{$this->context}**, you've been added to the Traitz Academy Community.";
        }

        return $source
            ? "Following your {$source} with Traitz Academy, you've been added to the Traitz Academy Community."
            : "You've been added to the Traitz Academy Community.";
    }
}
