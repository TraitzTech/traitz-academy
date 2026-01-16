<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Application $application)
    {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject("New Application Received: {$this->application->first_name} {$this->application->last_name}")
            ->greeting('New Application Alert!')
            ->line("A new student has applied to one of your programs.")
            ->line('')
            ->line('**Application Details:**')
            ->line("- **Program:** {$this->application->program->title}")
            ->line("- **Name:** {$this->application->first_name} {$this->application->last_name}")
            ->line("- **Email:** {$this->application->email}")
            ->line("- **Phone:** {$this->application->phone}")
            ->line("- **Country:** {$this->application->country}")
            ->line("- **Education Level:** {$this->application->education_level}")
            ->line("- **Institution:** {$this->application->institution_name}")
            ->line("")
            ->line('**Student Motivation:**')
            ->line($this->application->motivation)
            ->line('')
            ->line('**Experience:**')
            ->line($this->application->experience)
            ->line('')
            ->action('Review Application', route('admin.applications.show', $this->application->id))
            ->line('---')
            ->line("This is an automated notification from {$siteName}.");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'program_id' => $this->application->program_id,
            'applicant_name' => "{$this->application->first_name} {$this->application->last_name}",
            'applicant_email' => $this->application->email,
        ];
    }
}
