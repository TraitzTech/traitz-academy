<?php

namespace App\Http\Controllers;

use App\Helpers\SettingHelper;
use App\Models\Application;
use App\Models\Interview;
use App\Models\InterviewAnswer;
use App\Models\InterviewResponse;
use App\Notifications\InterviewCompletedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    public function show(Interview $interview): Response
    {
        $user = auth()->user();

        // Check if user already completed this interview
        $existingResponse = InterviewResponse::where('interview_id', $interview->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->first();

        if ($existingResponse) {
            return Inertia::render('Interviews/Result', [
                'interview' => $interview,
                'response' => $existingResponse->load('answers.question'),
            ]);
        }

        $interview->load(['questions' => function ($query) {
            $query->orderBy('sort_order');
        }]);

        // Don't expose correct answers to the frontend
        $interview->questions->transform(function ($question) {
            $question->makeHidden('correct_answer');

            return $question;
        });

        return Inertia::render('Interviews/Take', [
            'interview' => $interview,
        ]);
    }

    public function submit(Request $request, Interview $interview): RedirectResponse
    {
        $user = auth()->user();

        // Prevent double submission
        $existingResponse = InterviewResponse::where('interview_id', $interview->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->first();

        if ($existingResponse) {
            return redirect()->route('interviews.result', $interview->id)
                ->with('info', 'You have already completed this interview.');
        }

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:interview_questions,id',
            'answers.*.answer' => 'nullable|string|max:5000',
            'application_id' => 'nullable|exists:applications,id',
        ]);

        $questions = $interview->questions()->get()->keyBy('id');
        $totalPoints = $questions->sum('points');
        $earnedPoints = 0;

        // Determine if all questions are auto-gradeable (MCQ or boolean)
        $hasTextQuestions = $questions->contains(fn ($q) => $q->type === 'text');
        $requiresManualReview = $hasTextQuestions;

        // Create the response record
        $applicationId = $validated['application_id'] ?? null;
        if (! $applicationId) {
            $linkedApplication = Application::where('user_id', $user->id)
                ->where('interview_id', $interview->id)
                ->first();
            $applicationId = $linkedApplication?->id;
        }

        $response = InterviewResponse::create([
            'interview_id' => $interview->id,
            'user_id' => $user->id,
            'application_id' => $applicationId,
            'total_points' => $totalPoints,
            'status' => 'completed',
            'requires_manual_review' => $requiresManualReview,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        // Process each answer
        foreach ($validated['answers'] as $answerData) {
            $question = $questions->get($answerData['question_id']);
            if (! $question) {
                continue;
            }

            $isCorrect = false;
            $pointsEarned = 0;

            if ($question->type === 'text') {
                // Text answers require manual review — don't auto-grade
                $isCorrect = false;
                $pointsEarned = 0;
            } else {
                // For multiple_choice and boolean, check against correct answer
                $isCorrect = strtolower(trim($answerData['answer'] ?? '')) === strtolower(trim($question->correct_answer ?? ''));
                $pointsEarned = $isCorrect ? $question->points : 0;
            }

            $earnedPoints += $pointsEarned;

            InterviewAnswer::create([
                'interview_response_id' => $response->id,
                'interview_question_id' => $question->id,
                'answer' => $answerData['answer'] ?? null,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
            ]);
        }

        if ($requiresManualReview) {
            // Only record the auto-gradeable portion; final score set after admin review
            $response->update([
                'score' => $earnedPoints,
                'percentage' => 0,
                'passed' => false,
            ]);
        } else {
            // All questions are auto-gradeable — calculate final score
            $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
            $passed = $percentage >= $interview->passing_score;

            $response->update([
                'score' => $earnedPoints,
                'percentage' => $percentage,
                'passed' => $passed,
            ]);
        }

        // Notify admin
        $adminEmail = SettingHelper::contactEmail() ?? config('mail.from.address');
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', $adminEmail)
            ->notify(new InterviewCompletedNotification($response));

        // Update interview status on linked applications
        Application::where('user_id', $user->id)
            ->where('interview_id', $interview->id)
            ->where('interview_status', 'scheduled')
            ->update(['interview_status' => 'completed']);

        return redirect()->route('interviews.result', $interview->id)
            ->with('success', $requiresManualReview
                ? 'Interview submitted successfully! Your responses are under review.'
                : 'Interview submitted successfully! Your results are shown below.');
    }

    public function result(Interview $interview): Response
    {
        $user = auth()->user();

        $response = InterviewResponse::where('interview_id', $interview->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['answers.question', 'interview'])
            ->firstOrFail();

        return Inertia::render('Interviews/Result', [
            'interview' => $interview,
            'response' => $response,
        ]);
    }
}
