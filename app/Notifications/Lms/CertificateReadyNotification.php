<?php

namespace App\Notifications\Lms;

use App\Models\Certificate;
use App\Models\User;
use App\Notifications\Concerns\UsesLmsNotificationPreferences;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class CertificateReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesLmsNotificationPreferences;

    public function __construct(
        public Certificate $certificate
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        return $this->channelsFor(
            $notifiable,
            LmsNotificationPreference::CERTIFICATE_DELIVERY,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->certificate->loadMissing('course:id,title');

        $mail = (new MailMessage)
            ->subject('Your certificate is ready — '.$this->certificate->course?->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('Your certificate for **'.$this->certificate->course?->title.'** is attached to this email.')
            ->line('Verification code: **'.$this->certificate->verification_code.'**.');

        $disk = config('filesystems.default', 'local');
        $path = $this->certificate->pdf_path;
        if ($path && Storage::disk($disk)->exists($path)) {
            $mail->attachData(
                Storage::disk($disk)->get($path),
                'certificate-'.$this->certificate->verification_code.'.pdf',
                ['mime' => 'application/pdf']
            );
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->certificate->loadMissing('course:id,title');

        return [
            'type' => 'certificate_ready',
            'title' => 'Certificate ready',
            'body' => $this->certificate->course?->title ?? 'Course',
            'url' => '/dashboard/my-courses',
            'certificate_id' => $this->certificate->id,
            'course_id' => $this->certificate->course_id,
        ];
    }
}
