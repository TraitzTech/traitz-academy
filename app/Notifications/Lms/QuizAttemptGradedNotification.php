<?php

namespace App\Notifications\Lms;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\Concerns\UsesLmsNotificationPreferences;
use App\Support\Lms\LmsNotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuizAttemptGradedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use UsesLmsNotificationPreferences;

    public function __construct(
        public Quiz $quiz,
        public QuizAttempt $attempt,
        public Course $course
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
            LmsNotificationPreference::QUIZ_GRADED,
            includeMail: true,
            includeDatabase: true
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resultUrl = route('lms.quizzes.result', [$this->quiz, $this->attempt]);
        $score = $this->attempt->score_percentage !== null
            ? number_format((float) $this->attempt->score_percentage, 1).'%'
            : 'graded';

        $mail = (new MailMessage)
            ->subject('Quiz graded — '.$this->quiz->title)
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line('Your instructor graded **'.$this->quiz->title.'** in **'.$this->course->title.'**.')
            ->line('Score: **'.$score.'**.');

        if ($this->attempt->instructor_feedback) {
            $mail->line('Feedback: '.$this->attempt->instructor_feedback);
        }

        return $mail->action('View results', $resultUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'quiz_attempt_graded',
            'title' => 'Quiz graded',
            'body' => $this->quiz->title,
            'url' => '/dashboard/quizzes/'.$this->quiz->id.'/attempts/'.$this->attempt->id.'/result',
            'quiz_id' => $this->quiz->id,
            'attempt_id' => $this->attempt->id,
            'course_id' => $this->course->id,
        ];
    }
}
