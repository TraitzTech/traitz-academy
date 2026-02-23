<?php

use App\Models\Application;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('requires cv for professional internship applications', function () {
    Notification::fake();

    $user = User::factory()->create();
    $program = Program::factory()->create([
        'category' => 'professional-internship',
    ]);

    $response = $this->actingAs($user)->post(route('applications.store'), [
        'program_id' => $program->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'phone' => '+237670000000',
        'country' => 'Cameroon',
        'bio' => 'Frontend engineer.',
        'education_level' => 'Bachelor',
        'motivation' => 'I want to gain hands-on professional industry experience quickly.',
        'experience' => '1 year internship experience.',
    ]);

    $response->assertSessionHasErrors('cv');
});

it('stores cv for job opportunity applications and sets type to job', function () {
    Storage::fake('public');
    Notification::fake();

    $user = User::factory()->create();
    $program = Program::factory()->create([
        'category' => 'job-opportunity',
    ]);

    $response = $this->actingAs($user)->post(route('applications.store'), [
        'program_id' => $program->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+237680000000',
        'country' => 'Cameroon',
        'bio' => 'Backend developer.',
        'education_level' => 'Bachelor',
        'motivation' => 'I am excited to contribute and learn in a real production environment.',
        'experience' => 'Built Laravel apps and REST APIs.',
        'cv' => UploadedFile::fake()->create('resume.pdf', 200, 'application/pdf'),
    ]);

    $response->assertRedirect('/dashboard');

    $application = Application::query()->latest('id')->first();

    expect($application)->not()->toBeNull();
    expect($application->application_type)->toBe('job');
    expect($application->cv_path)->not()->toBeNull();

    Storage::disk('public')->assertExists($application->cv_path);
});
