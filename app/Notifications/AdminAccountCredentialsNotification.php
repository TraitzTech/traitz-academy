<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAccountCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $temporaryPassword,
        private readonly User $createdBy,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));

        return (new MailMessage)
            ->subject('Your '.$siteName.' account has been created')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('An executive at Traitz Academy created an account for you.')
            ->line('Email: '.$notifiable->email)
            ->line('Temporary password: '.$this->temporaryPassword)
            ->line('Role: '.$this->formatRole((string) $notifiable->role))
            ->action('Login to your account', route('login'))
            ->line('Please sign in and change your password immediately from your account settings.')
            ->line('Created by: '.$this->createdBy->name)
            ->salutation('Regards, '.$siteName);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'created_by' => $this->createdBy->id,
            'email' => $notifiable->email,
            'role' => $notifiable->role,
        ];
    }

    private function formatRole(string $role): string
    {
        return str($role)->replace('_', ' ')->title()->toString();
    }
}
