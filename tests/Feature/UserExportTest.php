<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
});

// ==============================
// Phone Number Display Tests
// ==============================

it('shows phone number on admin users index page', function () {
    User::factory()->create(['name' => 'John Doe', 'phone' => '+1234567890', 'role' => 'user']);

    $response = $this->actingAs($this->admin)->get('/admin/users');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users.data', 2)
    );
});

it('shows phone number on admin user show page', function () {
    $user = User::factory()->create(['phone' => '+9876543210']);

    $response = $this->actingAs($this->admin)->get("/admin/users/{$user->id}");

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Show')
        ->where('user.phone', '+9876543210')
    );
});

// ==============================
// Export Tests
// ==============================

it('exports users as CSV', function () {
    User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com', 'phone' => '+111', 'role' => 'user']);

    $response = $this->actingAs($this->admin)->get('/admin/users/export?format=csv');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    $content = $response->streamedContent();
    expect($content)->toContain('Test User')
        ->toContain('test@example.com')
        ->toContain('+111');
});

it('exports users as Excel CSV format', function () {
    User::factory()->create(['name' => 'Excel User', 'role' => 'user']);

    $response = $this->actingAs($this->admin)->get('/admin/users/export?format=xlsx');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/csv; charset=utf-8');
    expect($response->streamedContent())->toContain('Excel User');
});

it('exports phone numbers only', function () {
    User::factory()->create(['phone' => '+111222333', 'role' => 'user']);
    User::factory()->create(['phone' => '+444555666', 'role' => 'user']);
    User::factory()->create(['phone' => null, 'role' => 'user']);

    $response = $this->actingAs($this->admin)->get('/admin/users/export?format=phones');

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'text/plain; charset=utf-8');
    $content = $response->streamedContent();
    expect($content)->toContain('+111222333')
        ->toContain('+444555666')
        ->not->toContain('Name');
});

it('filters export by role', function () {
    User::factory()->create(['name' => 'Admin Bob', 'role' => 'admin']);
    User::factory()->create(['name' => 'Normal Jane', 'role' => 'user']);

    $response = $this->actingAs($this->admin)->get('/admin/users/export?format=csv&role=user');

    $response->assertSuccessful();
    $content = $response->streamedContent();
    expect($content)->toContain('Normal Jane')
        ->not->toContain('Admin Bob');
});

it('filters export by search term', function () {
    User::factory()->create(['name' => 'Alice Smith', 'email' => 'alice@example.com', 'role' => 'user']);
    User::factory()->create(['name' => 'Bob Jones', 'email' => 'bob@example.com', 'role' => 'user']);

    $response = $this->actingAs($this->admin)->get('/admin/users/export?format=csv&search=Alice');

    $response->assertSuccessful();
    $content = $response->streamedContent();
    expect($content)->toContain('Alice Smith')
        ->not->toContain('Bob Jones');
});

it('validates export format parameter', function () {
    $response = $this->actingAs($this->admin)->get('/admin/users/export?format=invalid');

    $response->assertRedirect();
});

it('requires admin role to export', function () {
    $user = User::factory()->create(['role' => 'user']);

    $response = $this->actingAs($user)->get('/admin/users/export?format=csv');

    $response->assertForbidden();
});

it('can search users by phone number', function () {
    User::factory()->create(['name' => 'Phone Guy', 'phone' => '+9998887776', 'role' => 'user']);
    User::factory()->create(['name' => 'No Match', 'phone' => '+1112223334', 'role' => 'user']);

    $response = $this->actingAs($this->admin)->get('/admin/users?search=999888');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users.data', 1)
        ->where('users.data.0.name', 'Phone Guy')
    );
});
