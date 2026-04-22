<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    public function show(Quiz $quiz): Response
    {
        $this->authorizeStudentAccess($quiz);

        $quiz->load([
            'course:id,title,slug',
            'lesson' => fn ($q) => $q->select('id', 'title', 'course_id')->with('attachments'),
            'questions' => fn ($q) => $q->ordered(),
        ]);

        $userId = (int) auth()->id();

        $pendingReview = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->first();

        if ($pendingReview) {
            return Inertia::render('Lms/Quizzes/PendingReview', [
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'course' => $quiz->course,
                    'lesson' => $quiz->lesson,
                    'pass_mark_percentage' => (float) $quiz->pass_mark_percentage,
                ],
                'attempt' => [
                    'id' => $pendingReview->id,
                    'submitted_at' => optional($pendingReview->submitted_at)->toIso8601String(),
                ],
            ]);
        }

        $attempt = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();

        $attemptCount = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if (! $attempt) {
            if ($quiz->max_attempts !== null && $attemptCount >= $quiz->max_attempts) {
                $lastGraded = QuizAttempt::query()
                    ->where('quiz_id', $quiz->id)
                    ->where('user_id', $userId)
                    ->where('status', 'graded')
                    ->latest('graded_at')
                    ->first();

                if ($lastGraded) {
                    return redirect()->route('lms.quizzes.result', [$quiz, $lastGraded]);
                }

                abort(403, 'Maximum attempts reached.');
            }

            $attempt = QuizAttempt::create([
                'user_id' => $userId,
                'quiz_id' => $quiz->id,
                'answers' => [],
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $publicQuestions = $quiz->questions->map(function ($q) {
            $data = $q->only(['id', 'question', 'type', 'options', 'points', 'sort_order']);
            $data['options'] = $q->options ?? [];

            return $data;
        })->values();

        return Inertia::render('Lms/Quizzes/Take', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'instructions' => $quiz->instructions,
                'pass_mark_percentage' => (float) $quiz->pass_mark_percentage,
                'max_attempts' => $quiz->max_attempts,
                'reveal_answers' => (bool) $quiz->reveal_answers,
                'course' => $quiz->course,
                'lesson' => $quiz->lesson
                    ? [
                        'id' => $quiz->lesson->id,
                        'title' => $quiz->lesson->title,
                        'attachments' => $quiz->lesson->attachments->map(fn ($a) => [
                            'id' => $a->id,
                            'name' => $a->name,
                            'file_url' => $a->file_url,
                            'file_type' => $a->file_type,
                            'file_size' => $a->file_size,
                            'formatted_file_size' => $a->formatted_file_size,
                        ])->values(),
                    ]
                    : null,
                'questions' => $publicQuestions,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'answers' => $attempt->answers ?? [],
                'started_at' => optional($attempt->started_at)->toIso8601String(),
            ],
            'attemptCount' => $attemptCount,
        ]);
    }

    public function saveProgress(Request $request, Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        $this->authorizeAttempt($quiz, $attempt);

        abort_unless($attempt->status === 'in_progress', 403);

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable'],
        ]);

        $attempt->update([
            'answers' => $validated['answers'],
            'status' => 'in_progress',
        ]);

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        $this->authorizeAttempt($quiz, $attempt);

        abort_unless($attempt->status === 'in_progress', 403);

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable'],
        ]);

        $attempt->update([
            'answers' => $validated['answers'],
            'status' => 'submitted',
            'submitted_at' => now(),
            'score_percentage' => null,
            'passed' => null,
            'graded_at' => null,
        ]);

        return redirect()->route('lms.quizzes.take', $quiz);
    }

    public function result(Quiz $quiz, QuizAttempt $attempt): Response
    {
        $this->authorizeAttempt($quiz, $attempt);

        abort_if($attempt->status === 'in_progress', 403);

        $quiz->load(['course:id,title,slug', 'lesson:id,title', 'questions' => fn ($q) => $q->ordered()]);

        $questions = $quiz->questions->map(function ($q) use ($attempt, $quiz) {
            $submitted = $attempt->answers[(string) $q->id] ?? null;
            $correct = $q->correct_answer ?? [];

            return [
                'id' => $q->id,
                'question' => $q->question,
                'type' => $q->type,
                'options' => $q->options ?? [],
                'points' => $q->points,
                'submitted' => $submitted,
                'correct' => $quiz->reveal_answers ? $correct : null,
                'explanation' => $quiz->reveal_answers ? $q->explanation : null,
            ];
        })->values();

        return Inertia::render('Lms/Quizzes/Result', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'pass_mark_percentage' => (float) $quiz->pass_mark_percentage,
                'reveal_answers' => (bool) $quiz->reveal_answers,
                'course' => $quiz->course,
                'lesson' => $quiz->lesson,
                'questions' => $questions,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'score_percentage' => (float) ($attempt->score_percentage ?? 0),
                'passed' => (bool) $attempt->passed,
                'submitted_at' => optional($attempt->submitted_at)->toIso8601String(),
                'instructor_feedback' => $attempt->instructor_feedback,
            ],
        ]);
    }

    private function authorizeStudentAccess(Quiz $quiz): void
    {
        $quiz->loadMissing('course');

        $enrolled = Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $quiz->course_id)
            ->whereIn('access_status', ['active', 'completed'])
            ->exists();

        abort_unless($enrolled, 403);
    }

    private function authorizeAttempt(Quiz $quiz, QuizAttempt $attempt): void
    {
        $this->authorizeStudentAccess($quiz);
        abort_unless($attempt->quiz_id === $quiz->id && $attempt->user_id === auth()->id(), 403);
    }
}
