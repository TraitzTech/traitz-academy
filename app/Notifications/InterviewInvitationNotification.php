<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\Application;
use App\Models\Interview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Application $application,
        private Interview $interview
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
        $applicantName = $this->application->first_name;
        $programTitle = $this->application->program->title ?? 'the program';
        $interviewUrl = url("/interviews/{$this->interview->id}");

        return (new MailMessage)
            ->subject("You're Invited to an Interview — {$programTitle}")
            ->view('emails.interview-invitation', [
                'applicantName' => $applicantName,
                'siteName' => $siteName,
                'programTitle' => $programTitle,
                'interviewTitle' => $this->interview->title,
                'interviewDescription' => $this->interview->description,
                'questionCount' => $this->interview->questions()->count(),
                'timeLimit' => $this->interview->time_limit_minutes,
                'passingScore' => $this->interview->passing_score,
                'interviewUrl' => $interviewUrl,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'interview_id' => $this->interview->id,
            'program_id' => $this->application->program_id,
        ];
    }
}
