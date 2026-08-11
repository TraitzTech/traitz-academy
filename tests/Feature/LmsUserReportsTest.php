<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('renders admin user reports without error', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);

    $response = $this->actingAs($admin)->get(route('admin.lms.user-reports'));

    $response->assertSuccessful();
});

it('filters admin user reports by enrollment status without error', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);

    $response = $this->actingAs($admin)->get(route('admin.lms.user-reports', ['status' => 'active']));

    $response->assertSuccessful();
});
