<?php

namespace App\Notifications\Admin;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Replaces the old one-email-per-signup admin notifications for community
 * joins, applications, event and AI Forge registrations with a single
 * weekly roundup — those fired on every signup regardless of volume, which
 * was mostly noise admin didn't act on immediately.
 *
 * Each section is `['count' => int, 'breakdown' => array<string, int>]`,
 * keyed by whatever the section groups by (track, program, event name).
 */
class WeeklyActivityDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{count: int, breakdown: array<string, int>}  $communityJoins
     * @param  array{count: int, breakdown: array<string, int>}  $applications
     * @param  array{count: int, breakdown: array<string, int>}  $eventRegistrations
     * @param  array{count: int, breakdown: array<string, int>}  $aiForgeRegistrations
     */
    public function __construct(
        private readonly CarbonInterface $periodStart,
        private readonly CarbonInterface $periodEnd,
        private readonly array $communityJoins,
        private readonly array $applications,
        private readonly array $eventRegistrations,
        private readonly array $aiForgeRegistrations,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $total = $this->communityJoins['count'] + $this->applications['count']
            + $this->eventRegistrations['count'] + $this->aiForgeRegistrations['count'];

        $message = (new MailMessage)
            ->subject('Weekly signups digest: '.$this->periodStart->format('d M').' – '.$this->periodEnd->format('d M Y'))
            ->greeting('This week on Traitz Academy')
            ->line("{$total} new signup(s) across community, applications and events — one roundup instead of an email per signup.");

        $this->addSection($message, 'Community joins', $this->communityJoins, url('/admin/community/members'));
        $this->addSection($message, 'Program & internship applications', $this->applications, url('/admin/applications'));
        $this->addSection($message, 'Event registrations', $this->eventRegistrations, url('/admin/events'));
        $this->addSection($message, 'AI Forge registrations', $this->aiForgeRegistrations, url('/admin/ai-forge/registrations'));

        return $message;
    }

    /**
     * @param  array{count: int, breakdown: array<string, int>}  $stats
     */
    private function addSection(MailMessage $message, string $title, array $stats, string $url): void
    {
        if ($stats['count'] === 0) {
            return;
        }

        $message->line("**{$title}: {$stats['count']}**");

        foreach ($stats['breakdown'] as $label => $count) {
            $message->line("- {$label}: {$count}");
        }

        $message->line("[View {$title} →]({$url})");
    }
}
