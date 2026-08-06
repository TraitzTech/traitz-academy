<?php

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;

it('updates a course when category_id is sent as an empty string', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);

    $category = CourseCategory::query()->create([
        'name' => 'Test Category',
        'slug' => 'test-cat-'.uniqid(),
        'description' => null,
        'icon' => null,
        'color' => null,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $course = Course::query()->create([
        'instructor_id' => null,
        'category_id' => $category->id,
        'title' => 'Test Course',
        'slug' => 'test-course-'.uniqid(),
        'short_description' => str_repeat('a', 50),
        'description' => null,
        'level' => 'beginner',
        'status' => 'draft',
        'price' => 100,
        'sale_price' => null,
        'duration' => null,
        'is_featured' => false,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.courses.update', $course), [
        'title' => 'Updated Title',
        'category_id' => '',
        'level' => 'beginner',
        'short_description' => str_repeat('b', 50),
        'description' => '',
        'duration' => '',
        'status' => 'draft',
    ]);

    $response->assertRedirect(route('admin.courses.edit', $course));
    $course->refresh();
    expect($course->title)->toBe('Updated Title')
        ->and($course->category_id)->toBeNull()
        ->and((float) $course->price)->toBe(100.0)
        ->and($course->sale_price)->toBeNull();
});

it('updates course pricing with max installments like programs', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);

    $course = Course::query()->create([
        'instructor_id' => null,
        'category_id' => null,
        'title' => 'Priced Course',
        'slug' => 'priced-course-'.uniqid(),
        'short_description' => str_repeat('a', 50),
        'description' => null,
        'level' => 'beginner',
        'status' => 'draft',
        'price' => 100_000,
        'sale_price' => null,
        'max_installments' => 1,
        'duration' => null,
        'is_featured' => false,
    ]);

    $this->actingAs($admin)->put(route('admin.courses.pricing.update', $course), [
        'price' => '100000',
        'sale_price' => '',
        'max_installments' => 4,
        'is_featured' => true,
    ])->assertRedirect();

    $course->refresh();

    expect($course->max_installments)->toBe(4)
        ->and($course->is_featured)->toBeTrue()
        ->and((float) $course->price)->toBe(100000.0);
});

it('forces max installments to 1 when course price is zero', function () {
    $admin = User::factory()->create(['role' => User::ROLE_CTO]);

    $course = Course::query()->create([
        'instructor_id' => null,
        'category_id' => null,
        'title' => 'Free Course',
        'slug' => 'free-course-'.uniqid(),
        'short_description' => str_repeat('a', 50),
        'description' => null,
        'level' => 'beginner',
        'status' => 'draft',
        'price' => 0,
        'sale_price' => null,
        'max_installments' => 3,
        'duration' => null,
        'is_featured' => false,
    ]);

    $this->actingAs($admin)->put(route('admin.courses.pricing.update', $course), [
        'price' => '0',
        'sale_price' => '',
        'max_installments' => 4,
        'is_featured' => false,
    ])->assertRedirect();

    expect($course->fresh()->max_installments)->toBe(1);
});
