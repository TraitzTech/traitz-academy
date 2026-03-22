<?php

use App\Models\Application;
use App\Models\Interview;
use App\Models\InterviewQuestion;
use App\Models\InterviewResponse;
use App\Models\Program;
use App\Models\User;
use App\Notifications\BatchEmailNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'phone' => '1234567890']);
    $this->user = User::factory()->create(['role' => 'user', 'phone' => '0987654321']);
    $this->program = Program::factory()->create(['category' => 'job-opportunity']);
});

// ==============================
// Auto-graded Interview Rejection
// ==============================

it('auto-rejects application when user fails an auto-graded interview', function () {
    Notification::fake();

    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 80,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'What is 2+2?',
        'type' => 'multiple_choice',
        'options' => ['3', '4', '5'],
        'correct_answer' => '4',
        'points' => 10,
        'sort_order' => 0,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Is PHP a programming language?',
        'type' => 'boolean',
        'correct_answer' => 'true',
        'points' => 10,
        'sort_order' => 1,
    ]);

    $application = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $this->user->id,
        'interview_id' => $interview->id,
        'interview_status' => 'scheduled',
        'status' => 'pending',
    ]);

    // Answer one correctly, one incorrectly → 50% < 80% passing score
    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $interview->questions[0]->id, 'answer' => '4'],
                ['question_id' => $interview->questions[1]->id, 'answer' => 'false'],
            ],
            'application_id' => $application->id,
        ])
        ->assertRedirect();

    $application->refresh();

    expect($application->status)->toBe('rejected')
        ->and($application->notes)->toContain('Auto-rejected')
        ->and($application->reviewed_at)->not->toBeNull();

    Notification::assertSentTo($this->user, BatchEmailNotification::class, function ($notification) {
        return str_contains($notification->subject, 'Update on Your Application')
            && str_contains($notification->messageHtml, 'did not meet the minimum requirement');
    });
});

it('does not reject application when user passes an auto-graded interview', function () {
    Notification::fake();

    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 50,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'What is 2+2?',
        'type' => 'multiple_choice',
        'options' => ['3', '4', '5'],
        'correct_answer' => '4',
        'points' => 10,
        'sort_order' => 0,
    ]);

    $application = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $this->user->id,
        'interview_id' => $interview->id,
        'interview_status' => 'scheduled',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $interview->questions[0]->id, 'answer' => '4'],
            ],
            'application_id' => $application->id,
        ])
        ->assertRedirect();

    $application->refresh();

    expect($application->status)->toBe('pending')
        ->and($application->interview_status)->toBe('completed');

    Notification::assertNotSentTo($this->user, BatchEmailNotification::class);
});

it('does not auto-reject when interview requires manual review', function () {
    Notification::fake();

    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 50,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Describe your experience',
        'type' => 'text',
        'points' => 10,
        'sort_order' => 0,
    ]);

    $application = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $this->user->id,
        'interview_id' => $interview->id,
        'interview_status' => 'scheduled',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $interview->questions[0]->id, 'answer' => 'Some text answer'],
            ],
            'application_id' => $application->id,
        ])
        ->assertRedirect();

    $application->refresh();

    // Should stay pending — admin needs to review first
    expect($application->status)->toBe('pending');

    Notification::assertNotSentTo($this->user, BatchEmailNotification::class);
});

// ==============================
// Admin Review Auto-Rejection
// ==============================

it('auto-rejects application when admin review results in a failing score', function () {
    Notification::fake();

    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 80,
    ]);

    $textQuestion = InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Describe your experience',
        'type' => 'text',
        'points' => 10,
        'sort_order' => 0,
    ]);

    $application = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $this->user->id,
        'interview_id' => $interview->id,
        'interview_status' => 'completed',
        'status' => 'pending',
    ]);

    // Submit interview as user
    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $textQuestion->id, 'answer' => 'Poor answer'],
            ],
            'application_id' => $application->id,
        ]);

    $response = InterviewResponse::where('user_id', $this->user->id)
        ->where('interview_id', $interview->id)
        ->first();
    $textAnswer = $response->answers()->first();

    // Admin gives a low score: 2/10 = 20% < 80% passing
    $this->actingAs($this->admin)
        ->post("/admin/interviews/{$interview->id}/responses/{$response->id}/review", [
            'scores' => [
                (string) $textAnswer->id => 2,
            ],
        ])
        ->assertRedirect();

    $application->refresh();

    expect($application->status)->toBe('rejected')
        ->and($application->notes)->toContain('Auto-rejected')
        ->and($application->reviewed_at)->not->toBeNull();

    Notification::assertSentTo($this->user, BatchEmailNotification::class, function ($notification) {
        return str_contains($notification->messageHtml, 'did not meet the minimum requirement');
    });
});

it('does not reject application when admin review results in a passing score', function () {
    Notification::fake();

    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 50,
    ]);

    $textQuestion = InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Describe your experience',
        'type' => 'text',
        'points' => 10,
        'sort_order' => 0,
    ]);

    $application = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $this->user->id,
        'interview_id' => $interview->id,
        'interview_status' => 'completed',
        'status' => 'pending',
    ]);

    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $textQuestion->id, 'answer' => 'Great answer'],
            ],
            'application_id' => $application->id,
        ]);

    $response = InterviewResponse::where('user_id', $this->user->id)
        ->where('interview_id', $interview->id)
        ->first();
    $textAnswer = $response->answers()->first();

    // Admin gives a high score: 9/10 = 90% > 50% passing
    $this->actingAs($this->admin)
        ->post("/admin/interviews/{$interview->id}/responses/{$response->id}/review", [
            'scores' => [
                (string) $textAnswer->id => 9,
            ],
        ])
        ->assertRedirect();

    $application->refresh();

    expect($application->status)->toBe('pending');

    Notification::assertNotSentTo($this->user, BatchEmailNotification::class);
});
