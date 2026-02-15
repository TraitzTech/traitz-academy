<?php

namespace App\Notifications;

use App\Models\InterviewResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewCompletedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public InterviewResponse $response
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $response = $this->response;
        $interview = $response->interview;
        $user = $response->user;

        $mail = (new MailMessage)
            ->greeting('Interview Submission Received')
            ->line("**{$user->name}** has completed the interview **{$interview->title}**.");

        if ($response->requires_manual_review) {
            $mail->subject("⏳ Review Required: {$user->name} - {$interview->title}")
                ->line('This interview contains open-ended questions that require your manual review.')
                ->line('Please review and score the responses in the admin panel.')
                ->action('Review Response', url('/admin/interviews/'.$interview->id.'/responses'));
        } else {
            $statusEmoji = $response->passed ? '✅' : '❌';
            $statusText = $response->passed ? 'Passed' : 'Did Not Pass';

            $mail->subject("Interview Completed: {$user->name} - {$interview->title}")
                ->line("**Score:** {$response->score}/{$response->total_points} ({$response->percentage}%)")
                ->line("**Result:** {$statusEmoji} {$statusText}")
                ->line("**Passing Score:** {$interview->passing_score}%")
                ->action('View in Admin Panel', url('/admin/interviews/'.$interview->id.'/responses'));
        }

        return $mail->line('Review the full interview response in the admin panel.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'interview_id' => $this->response->interview_id,
            'user_id' => $this->response->user_id,
            'score' => $this->response->score,
            'passed' => $this->response->passed,
        ];
    }
}
