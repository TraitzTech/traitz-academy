<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationAcceptanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Application $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));
        $whatsappCommunityLink = SettingHelper::whatsAppCommunityLink();
        $recipientName = $this->application->first_name ?: ($this->application->user?->name ?? 'there');

        $mail = (new MailMessage)
            ->subject('Congratulations! Your Application Has Been Accepted')
            ->greeting("Hello {$recipientName},")
            ->line("We are delighted to inform you that your application for **{$this->application->program->title}** has been accepted.")
            ->line('Welcome to Traitz Academy!')
            ->line('')
            ->line('Next Steps:')
            ->line('- Check your dashboard for program details')
            ->line('- Connect with other accepted applicants')
            ->line('- Reach out if you have any questions')
            ->action('View Your Dashboard', route('dashboard'));

        if ($whatsappCommunityLink) {
            $mail->line('')
                ->line('**Join Our Community**')
                ->line('Connect with our community on WhatsApp for updates, support, and to meet other students.')
                ->action('Join WhatsApp Community', $whatsappCommunityLink);
        }

        return $mail->line('')
            ->line('We look forward to working with you!')
            ->salutation("Best regards,\n{$siteName}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'program_id' => $this->application->program_id,
            'status' => 'accepted',
        ];
    }
}
