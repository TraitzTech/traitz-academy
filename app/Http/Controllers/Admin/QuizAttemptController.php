<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    public function index(Quiz $quiz): Response
    {
        $quiz->load(['course:id,title', 'lesson:id,title']);

        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Quizzes/Attempts', [
            'quiz' => $quiz,
            'attempts' => $attempts,
        ]);
    }

    public function show(Quiz $quiz, QuizAttempt $attempt): Response
    {
        abort_unless($attempt->quiz_id === $quiz->id, 404);

        $quiz->load(['questions' => fn ($q) => $q->ordered(), 'course:id,title', 'lesson:id,title']);
        $attempt->load('user:id,name,email');

        return Inertia::render('Admin/Quizzes/AttemptShow', [
            'quiz' => $quiz,
            'attempt' => $attempt,
        ]);
    }
}
