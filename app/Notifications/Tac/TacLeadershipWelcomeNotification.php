<?php

namespace App\Notifications\Tac;

use App\Helpers\SettingHelper;
use App\Models\TacLeader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent whenever somebody is appointed to a TAC leadership post. Always a warm
 * welcome to the role itself — what it means, what's expected — and, only
 * when a brand-new login was created in the same step, their credentials
 * folded into the same email rather than sent separately.
 */
class TacLeadershipWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private TacLeader $leader,
        private ?string $temporaryPassword = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $firstName = $this->firstName($notifiable);

        $message = (new MailMessage)
            ->subject("Welcome to TAC leadership, {$this->leader->name}!")
            ->greeting("Congratulations, {$firstName}!")
            ->line("You've been appointed **{$this->leader->roleLabel()}** for the Traitz Academy Community.")
            ->line($this->roleBlurb());

        $message->line('')
            ->line('**A little about TAC:** it\'s the year-round home base for Traitz Academy students, interns, mentors and tech enthusiasts — active every month, not just during internship cohorts. Leadership here is real: you set the pace for the people in your remit.');

        if ($this->temporaryPassword !== null) {
            $message->line('')
                ->line('**Your login details:**')
                ->line("- **Email:** {$this->leader->email}")
                ->line("- **Temporary password:** {$this->temporaryPassword}")
                ->line('Please sign in and change your password as soon as you can.');
        }

        return $message
            ->action('Go to my TAC dashboard', url('/admin/community'))
            ->line('If you have any questions about what\'s expected of you, reach out to the TAC leadership team — we\'re glad to have you.')
            ->salutation("Welcome aboard,\nThe {$siteName} team");
    }

    private function firstName(object $notifiable): string
    {
        $name = $notifiable->name ?? $notifiable->first_name ?? $this->leader->name;

        return trim(explode(' ', (string) $name)[0]) ?: 'there';
    }

    private function roleBlurb(): string
    {
        return match ($this->leader->role_type) {
            TacLeader::ROLE_LEAD => 'As **Lead**, you carry overall responsibility for TAC — every track, every member, every event. The community\'s health and direction run through you.',
            TacLeader::ROLE_CO_LEAD => 'As **Co-Lead**, you support the Lead in keeping TAC running day to day, and stand in for them when needed.',
            TacLeader::ROLE_SECRETARY => 'As **Secretary**, you keep TAC\'s records, communications and administration in order — the community runs smoother because of it.',
            TacLeader::ROLE_TECHNICAL_LEAD => 'As **Technical Lead**, you oversee technical direction and execution across every track in the community.',
            TacLeader::ROLE_TRACK_MENTOR => 'As **'.($this->leader->track?->name ?? 'Track').' Mentor**, you guide members in your track, run activities, and help them grow real skills.',
            TacLeader::ROLE_SCHOOL_LEAD => 'As **School Lead for '.($this->leader->school ?? 'your campus').'**, you represent and organise TAC\'s presence there.',
            TacLeader::ROLE_PARTNERSHIP_LEAD => 'As **Partnership Lead**, you bring in and manage the partners and sponsors who support TAC.',
            default => 'This role plays a real part in keeping TAC running — thank you for taking it on.',
        };
    }
}
