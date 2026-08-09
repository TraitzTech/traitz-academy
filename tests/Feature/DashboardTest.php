<?php

use App\Models\Application;
use App\Models\Program;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard caps inline payment summaries and links to the full list', function () {
    $user = User::factory()->create();

    Application::factory()->count(5)->create([
        'user_id' => $user->id,
        'program_id' => fn () => Program::factory()->create(['price' => 50000, 'max_installments' => 1]),
        'status' => 'accepted',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('paymentSummaries', 3)
            ->where('paymentSummariesTotal', 5)
        );
});

test('the dashboard does not advertise a full list when everything already fits inline', function () {
    $user = User::factory()->create();

    Application::factory()->count(2)->create([
        'user_id' => $user->id,
        'program_id' => fn () => Program::factory()->create(['price' => 50000, 'max_installments' => 1]),
        'status' => 'accepted',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('paymentSummaries', 2)
            ->where('paymentSummariesTotal', 2)
        );
});

test('the my programs page lists every accepted program without a cap', function () {
    $user = User::factory()->create();

    Application::factory()->count(5)->create([
        'user_id' => $user->id,
        'program_id' => fn () => Program::factory()->create(['price' => 50000, 'max_installments' => 1]),
        'status' => 'accepted',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.programs'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Programs')
            ->has('paymentSummaries', 5)
        );
});

test('guests are redirected away from the my programs page', function () {
    $this->get(route('dashboard.programs'))->assertRedirect(route('login'));
});
