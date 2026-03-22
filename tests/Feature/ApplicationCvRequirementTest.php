<?php

use App\Models\Application;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('requires cv when program setting marks it required', function () {
    Notification::fake();

    $user = User::factory()->create();
    $program = Program::factory()->create([
        'category' => 'professional-internship',
        'is_cv_required' => true,
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

it('stores cv when provided and sets type to job for job opportunity category', function () {
    Storage::fake('public');
    Notification::fake();

    $user = User::factory()->create();
    $program = Program::factory()->create([
        'category' => 'job-opportunity',
        'is_cv_required' => false,
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

it('allows submitting application without cv when program does not require cv', function () {
    Notification::fake();

    $user = User::factory()->create();
    $program = Program::factory()->create([
        'category' => 'professional-internship',
        'is_cv_required' => false,
    ]);

    $response = $this->actingAs($user)->post(route('applications.store'), [
        'program_id' => $program->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane-no-cv@example.com',
        'phone' => '+237670000123',
        'country' => 'Cameroon',
        'bio' => 'Frontend engineer.',
        'education_level' => 'Bachelor',
        'motivation' => 'I am motivated to join and contribute while building practical experience.',
        'experience' => 'Completed several web projects.',
    ]);

    $response->assertRedirect('/dashboard');

    $application = Application::query()->latest('id')->first();

    expect($application)->not()->toBeNull();
    expect($application->cv_path)->toBeNull();
});
