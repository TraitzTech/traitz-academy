<?php

use App\Models\Application;
use App\Models\Interview;
use App\Models\Program;
use App\Models\User;
use App\Notifications\InterviewInvitationNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'phone' => '1234567890']);
    $this->program = Program::factory()->create();
    $this->user = User::factory()->create(['role' => 'user', 'phone' => '0987654321']);
    $this->application = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => $this->user->id,
        'status' => 'pending',
    ]);
    $this->interview = Interview::factory()->create([
        'program_id' => $this->program->id,
        'is_active' => true,
    ]);
});

it('allows admin to schedule an interview for an application', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post("/admin/applications/{$this->application->id}/schedule-interview", [
            'interview_id' => $this->interview->id,
        ])
        ->assertRedirect();

    $this->application->refresh();

    expect($this->application->interview_id)->toBe($this->interview->id)
        ->and($this->application->interview_status)->toBe('scheduled')
        ->and($this->application->interview_scheduled_at)->not->toBeNull();

    Notification::assertSentTo($this->user, InterviewInvitationNotification::class);
});

it('rejects scheduling an interview from a different program', function () {
    $otherProgram = Program::factory()->create();
    $otherInterview = Interview::factory()->create([
        'program_id' => $otherProgram->id,
        'is_active' => true,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/applications/{$this->application->id}/schedule-interview", [
            'interview_id' => $otherInterview->id,
        ])
        ->assertStatus(404);

    $this->application->refresh();
    expect($this->application->interview_id)->toBeNull();
});

it('rejects scheduling an inactive interview', function () {
    $this->interview->update(['is_active' => false]);

    $this->actingAs($this->admin)
        ->post("/admin/applications/{$this->application->id}/schedule-interview", [
            'interview_id' => $this->interview->id,
        ])
        ->assertStatus(404);
});

it('rejects scheduling for application without a linked user', function () {
    $applicationNoUser = Application::factory()->create([
        'program_id' => $this->program->id,
        'user_id' => null,
        'status' => 'pending',
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/applications/{$applicationNoUser->id}/schedule-interview", [
            'interview_id' => $this->interview->id,
        ])
        ->assertRedirect();

    $applicationNoUser->refresh();
    expect($applicationNoUser->interview_id)->toBeNull();
});

it('shows available interviews on application show page', function () {
    $this->actingAs($this->admin)
        ->get("/admin/applications/{$this->application->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Applications/Show')
            ->has('availableInterviews', 1)
            ->has('application')
        );
});

it('requires authentication and admin role to schedule interview', function () {
    $this->post("/admin/applications/{$this->application->id}/schedule-interview", [
        'interview_id' => $this->interview->id,
    ])->assertRedirect('/login');

    $this->actingAs($this->user)
        ->post("/admin/applications/{$this->application->id}/schedule-interview", [
            'interview_id' => $this->interview->id,
        ])
        ->assertForbidden();
});

it('validates interview_id is required', function () {
    $this->actingAs($this->admin)
        ->post("/admin/applications/{$this->application->id}/schedule-interview", [])
        ->assertSessionHasErrors('interview_id');
});
