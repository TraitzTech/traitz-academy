<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\AiForgeRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAiForgeRegistration extends Notification
{
    use Queueable;

    public function __construct(private AiForgeRegistration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject("New AI Forge Registration - {$siteName}")
            ->greeting('New Registration!')
            ->line("**{$this->registration->first_name} {$this->registration->last_name}** has registered for AI Forge.")
            ->line("- **Email:** {$this->registration->email}")
            ->when($this->registration->phone, function (MailMessage $message) {
                return $message->line("- **Phone:** {$this->registration->phone}");
            })
            ->when($this->registration->organization, function (MailMessage $message) {
                return $message->line("- **Organization:** {$this->registration->organization}");
            })
            ->when($this->registration->motivation, function (MailMessage $message) {
                return $message->line("- **Motivation:** {$this->registration->motivation}");
            })
            ->action('View Registrations', url('/admin/ai-forge/registrations'))
            ->salutation("Best regards,\n{$siteName}");
    }
}
