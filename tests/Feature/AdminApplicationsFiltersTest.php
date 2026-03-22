<?php

use App\Models\Application;
use App\Models\Program;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('filters applications by country and interview status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $program = Program::factory()->create();

    Application::factory()->create([
        'program_id' => $program->id,
        'country' => 'Cameroon',
        'interview_status' => 'scheduled',
    ]);

    Application::factory()->create([
        'program_id' => $program->id,
        'country' => 'Nigeria',
        'interview_status' => 'completed',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.applications.index', [
        'country' => 'Cameroon',
        'interview_status' => 'scheduled',
    ]));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Applications/Index')
        ->where('filters.country', 'Cameroon')
        ->where('filters.interview_status', 'scheduled')
        ->has('applications.data', 1)
        ->where('applications.data.0.country', 'Cameroon')
        ->where('applications.data.0.interview_status', 'scheduled')
        ->has('countries')
    );
});
