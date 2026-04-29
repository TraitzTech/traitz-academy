<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Notifications\Lms\QuizAttemptGradedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    public function index(Quiz $quiz): Response
    {
        $this->authorizeQuiz($quiz);

        $quiz->load(['course:id,title', 'lesson:id,title']);

        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Tutor/Quizzes/Attempts', [
            'quiz' => $quiz,
            'attempts' => $attempts,
        ]);
    }

    public function show(Quiz $quiz, QuizAttempt $attempt): Response
    {
        $this->authorizeQuiz($quiz);
        abort_unless($attempt->quiz_id === $quiz->id, 404);

        $quiz->load(['questions' => fn ($q) => $q->ordered(), 'course:id,title', 'lesson:id,title']);
        $attempt->load('user:id,name,email');

        return Inertia::render('Tutor/Quizzes/AttemptShow', [
            'quiz' => $quiz,
            'attempt' => $attempt,
        ]);
    }

    public function grade(Request $request, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        $this->authorizeQuiz($quiz);
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        abort_unless($attempt->status === 'submitted', 422);

        $validated = $request->validate([
            'score_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'passed' => ['required', 'boolean'],
            'instructor_feedback' => ['nullable', 'string', 'max:10000'],
        ]);

        $attempt->update([
            'score_percentage' => $validated['score_percentage'],
            'passed' => $validated['passed'],
            'instructor_feedback' => $validated['instructor_feedback'] ?? null,
            'status' => 'graded',
            'graded_at' => now(),
        ]);

        $quiz->loadMissing('course');
        if ($quiz->course) {
            $attempt->loadMissing('user');
            if ($attempt->user) {
                $attempt->user->notify(new QuizAttemptGradedNotification($quiz, $attempt->fresh(), $quiz->course));
            }
        }

        return redirect()
            ->route('tutor.quizzes.attempts.show', [$quiz, $attempt])
            ->with('success', 'Attempt graded.');
    }

    private function authorizeQuiz(Quiz $quiz): void
    {
        $quiz->loadMissing('course:id,instructor_id');
        abort_unless($quiz->course && $quiz->course->instructor_id === auth()->id(), 403);
    }
}
