<?php

use App\Models\Application;
use App\Models\Interview;
use App\Models\InterviewQuestion;
use App\Models\InterviewResponse;
use App\Models\Program;
use App\Models\User;
use App\Notifications\InterviewInvitationNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'phone' => '1234567890']);
    $this->user = User::factory()->create(['role' => 'user', 'phone' => '0987654321']);
    $this->program = Program::factory()->create();
});

// ==============================
// Conditional Scoring Tests
// ==============================

it('auto-grades and shows score when all questions are MCQ', function () {
    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 50,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'What is 1+1?',
        'type' => 'multiple_choice',
        'options' => ['1', '2', '3', '4'],
        'correct_answer' => '2',
        'points' => 10,
        'sort_order' => 0,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Is the sky blue?',
        'type' => 'boolean',
        'correct_answer' => 'true',
        'points' => 10,
        'sort_order' => 1,
    ]);

    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $interview->questions[0]->id, 'answer' => '2'],
                ['question_id' => $interview->questions[1]->id, 'answer' => 'true'],
            ],
        ])
        ->assertRedirect();

    $response = InterviewResponse::where('user_id', $this->user->id)
        ->where('interview_id', $interview->id)
        ->first();

    expect($response->requires_manual_review)->toBeFalse()
        ->and($response->score)->toBe(20)
        ->and($response->percentage)->toBe('100.00')
        ->and($response->passed)->toBeTrue();
});

it('marks response as requiring manual review when text questions exist', function () {
    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 50,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'What is 1+1?',
        'type' => 'multiple_choice',
        'options' => ['1', '2', '3'],
        'correct_answer' => '2',
        'points' => 10,
        'sort_order' => 0,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Describe your experience',
        'type' => 'text',
        'points' => 10,
        'sort_order' => 1,
    ]);

    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $interview->questions[0]->id, 'answer' => '2'],
                ['question_id' => $interview->questions[1]->id, 'answer' => 'I have lots of experience'],
            ],
        ])
        ->assertRedirect();

    $response = InterviewResponse::where('user_id', $this->user->id)
        ->where('interview_id', $interview->id)
        ->first();

    expect($response->requires_manual_review)->toBeTrue()
        ->and($response->passed)->toBeFalse()
        ->and($response->percentage)->toBe('0.00');

    // Text answer should NOT be auto-graded
    $textAnswer = $response->answers()->whereHas('question', fn ($q) => $q->where('type', 'text'))->first();
    expect($textAnswer->is_correct)->toBeFalse()
        ->and($textAnswer->points_earned)->toBe(0);

    // MCQ answer should still be graded
    $mcqAnswer = $response->answers()->whereHas('question', fn ($q) => $q->where('type', 'multiple_choice'))->first();
    expect($mcqAnswer->is_correct)->toBeTrue()
        ->and($mcqAnswer->points_earned)->toBe(10);
});

it('shows pending review state on result page for text interviews', function () {
    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Describe yourself',
        'type' => 'text',
        'points' => 10,
        'sort_order' => 0,
    ]);

    // Submit the interview
    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $interview->questions()->first()->id, 'answer' => 'I am great'],
            ],
        ]);

    // Check result page
    $this->actingAs($this->user)
        ->get("/interviews/{$interview->id}/result")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Interviews/Result')
            ->where('response.requires_manual_review', true)
            ->where('response.reviewed_at', null)
        );
});

// ==============================
// Admin Manual Review Tests
// ==============================

it('allows admin to review and score text answers', function () {
    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 50,
    ]);

    $mcqQuestion = InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'What is 1+1?',
        'type' => 'multiple_choice',
        'options' => ['1', '2', '3'],
        'correct_answer' => '2',
        'points' => 10,
        'sort_order' => 0,
    ]);

    $textQuestion = InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Describe your experience',
        'type' => 'text',
        'points' => 10,
        'sort_order' => 1,
    ]);

    // Submit interview as user
    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $mcqQuestion->id, 'answer' => '2'],
                ['question_id' => $textQuestion->id, 'answer' => 'I have lots of experience'],
            ],
        ]);

    $response = InterviewResponse::where('user_id', $this->user->id)->first();
    $textAnswer = $response->answers()->where('interview_question_id', $textQuestion->id)->first();

    // Admin reviews and gives 8/10 for the text answer
    $this->actingAs($this->admin)
        ->post("/admin/interviews/{$interview->id}/responses/{$response->id}/review", [
            'scores' => [
                (string) $textAnswer->id => 8,
            ],
        ])
        ->assertRedirect();

    $response->refresh();

    expect($response->score)->toBe(18)
        ->and($response->percentage)->toBe('90.00')
        ->and($response->passed)->toBeTrue()
        ->and($response->reviewed_at)->not->toBeNull()
        ->and($response->reviewed_by)->toBe($this->admin->id);

    // Text answer should now be updated
    $textAnswer->refresh();
    expect($textAnswer->points_earned)->toBe(8)
        ->and($textAnswer->is_correct)->toBeTrue();
});

it('prevents reviewing a response that does not require manual review', function () {
    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
    ]);

    InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'What is 1+1?',
        'type' => 'multiple_choice',
        'options' => ['1', '2', '3'],
        'correct_answer' => '2',
        'points' => 10,
        'sort_order' => 0,
    ]);

    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $interview->questions()->first()->id, 'answer' => '2'],
            ],
        ]);

    $response = InterviewResponse::where('user_id', $this->user->id)->first();

    $this->actingAs($this->admin)
        ->post("/admin/interviews/{$interview->id}/responses/{$response->id}/review", [
            'scores' => [],
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('prevents double review of a response', function () {
    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
    ]);

    $textQuestion = InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Describe yourself',
        'type' => 'text',
        'points' => 10,
        'sort_order' => 0,
    ]);

    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $textQuestion->id, 'answer' => 'Hello'],
            ],
        ]);

    $response = InterviewResponse::where('user_id', $this->user->id)->first();
    $textAnswer = $response->answers()->first();

    // First review
    $this->actingAs($this->admin)
        ->post("/admin/interviews/{$interview->id}/responses/{$response->id}/review", [
            'scores' => [(string) $textAnswer->id => 5],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    // Second review should fail
    $this->actingAs($this->admin)
        ->post("/admin/interviews/{$interview->id}/responses/{$response->id}/review", [
            'scores' => [(string) $textAnswer->id => 10],
        ])
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('caps awarded points at question max', function () {
    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'passing_score' => 50,
    ]);

    $textQuestion = InterviewQuestion::create([
        'interview_id' => $interview->id,
        'question' => 'Describe yourself',
        'type' => 'text',
        'points' => 10,
        'sort_order' => 0,
    ]);

    $this->actingAs($this->user)
        ->post("/interviews/{$interview->id}/submit", [
            'answers' => [
                ['question_id' => $textQuestion->id, 'answer' => 'Great answer'],
            ],
        ]);

    $response = InterviewResponse::where('user_id', $this->user->id)->first();
    $textAnswer = $response->answers()->first();

    // Try to award 999 points (should be capped at 10)
    $this->actingAs($this->admin)
        ->post("/admin/interviews/{$interview->id}/responses/{$response->id}/review", [
            'scores' => [(string) $textAnswer->id => 999],
        ]);

    $textAnswer->refresh();
    expect($textAnswer->points_earned)->toBe(10);
});

// ==============================
// Bulk Interview Scheduling Tests
// ==============================

it('allows admin to bulk schedule interviews for multiple applications', function () {
    Notification::fake();

    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'is_active' => true,
    ]);

    $users = User::factory()->count(3)->create(['role' => 'user', 'phone' => '1234567890']);

    $applications = $users->map(fn ($user) => Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $user->id,
        'status' => 'pending',
    ]));

    $this->actingAs($this->admin)
        ->post('/admin/applications/bulk-schedule-interview', [
            'ids' => $applications->pluck('id')->toArray(),
            'interview_id' => $interview->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    foreach ($applications as $application) {
        $application->refresh();
        expect($application->interview_id)->toBe($interview->id)
            ->and($application->interview_status)->toBe('scheduled')
            ->and($application->interview_scheduled_at)->not->toBeNull();
    }

    Notification::assertSentTimes(InterviewInvitationNotification::class, 3);
});

it('skips applications already scheduled for the same interview in bulk', function () {
    Notification::fake();

    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'is_active' => true,
    ]);

    $user1 = User::factory()->create(['role' => 'user', 'phone' => '1111111111']);
    $user2 = User::factory()->create(['role' => 'user', 'phone' => '2222222222']);

    $app1 = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $user1->id,
        'interview_id' => $interview->id,
        'interview_status' => 'scheduled',
    ]);

    $app2 = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $user2->id,
    ]);

    $this->actingAs($this->admin)
        ->post('/admin/applications/bulk-schedule-interview', [
            'ids' => [$app1->id, $app2->id],
            'interview_id' => $interview->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    // Only app2 should get the notification
    Notification::assertSentTo($user2, InterviewInvitationNotification::class);
    Notification::assertNotSentTo($user1, InterviewInvitationNotification::class);
});

it('validates bulk schedule interview request', function () {
    $this->actingAs($this->admin)
        ->post('/admin/applications/bulk-schedule-interview', [])
        ->assertSessionHasErrors(['ids', 'interview_id']);
});

it('requires admin role for bulk scheduling', function () {
    $interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->post('/admin/applications/bulk-schedule-interview', [
            'ids' => [1],
            'interview_id' => $interview->id,
        ])
        ->assertForbidden();
});

it('provides interviews list on applications index page', function () {
    Interview::factory()->create([
        'program_id' => $this->program->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->get('/admin/applications')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Applications/Index')
            ->has('interviews', 1)
        );
});
