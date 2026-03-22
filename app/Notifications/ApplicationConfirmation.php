<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationConfirmation extends Notification
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

        return (new MailMessage)
            ->subject("Application Confirmation - {$siteName}")
            ->greeting("Hello {$this->application->first_name},")
            ->line("Thank you for applying to **{$this->application->program->title}**!")
            ->line('We have successfully received your application and will review it shortly.')
            ->line('We appreciate your interest and will get back to you with an update as soon as possible.')
            ->action('View Application', route('applications.index'))
            ->line('If you have any questions, feel free to contact us.')
            ->salutation("Best regards,\n{$siteName}");
    }
}
