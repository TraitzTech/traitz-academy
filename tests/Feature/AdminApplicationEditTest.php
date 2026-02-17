<?php

use App\Models\Application;
use App\Models\Interview;
use App\Models\Program;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('allows admin to open application edit page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $program = Program::factory()->create();

    $application = Application::factory()->create([
        'program_id' => $program->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.applications.edit', $application));

    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Applications/Edit')
        ->where('application.id', $application->id)
        ->has('programs')
    );
});

it('allows admin to update an application and change program', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $oldProgram = Program::factory()->create();
    $newProgram = Program::factory()->create();
    $interview = Interview::factory()->create(['program_id' => $oldProgram->id]);

    $application = Application::factory()->create([
        'program_id' => $oldProgram->id,
        'interview_id' => $interview->id,
        'interview_status' => 'scheduled',
        'interview_scheduled_at' => now(),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.applications.update', $application), [
        'program_id' => $newProgram->id,
        'first_name' => 'Updated',
        'last_name' => 'Applicant',
        'email' => 'updated@applicant.test',
        'phone' => '670123456',
        'country' => 'Cameroon',
        'status' => 'accepted',
        'notes' => 'Updated by admin.',
    ]);

    $response->assertRedirect(route('admin.applications.show', $application));
    $response->assertSessionHas('success');

    $application->refresh();

    expect($application->program_id)->toBe($newProgram->id)
        ->and($application->first_name)->toBe('Updated')
        ->and($application->last_name)->toBe('Applicant')
        ->and($application->email)->toBe('updated@applicant.test')
        ->and($application->status)->toBe('accepted')
        ->and($application->notes)->toBe('Updated by admin.')
        ->and($application->interview_id)->toBeNull()
        ->and($application->interview_status)->toBeNull()
        ->and($application->interview_scheduled_at)->toBeNull()
        ->and($application->reviewed_at)->not->toBeNull();
});
