<?php

use App\Models\SuccessStory;
use App\Models\User;

describe('Success Story Admin', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin']);
    });

    test('admin can view success stories index', function () {
        SuccessStory::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/admin/success-stories');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SuccessStories/Index')
            ->has('stories.data', 3)
        );
    });

    test('admin can view create success story page', function () {
        $response = $this->actingAs($this->admin)->get('/admin/success-stories/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SuccessStories/Create')
        );
    });

    test('admin can create success story', function () {
        $response = $this->actingAs($this->admin)->post('/admin/success-stories', [
            'name' => 'John Doe',
            'role' => 'Software Engineer',
            'company' => 'Tech Corp',
            'story' => 'This is my success story at the academy.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertRedirect('/admin/success-stories');
        $this->assertDatabaseHas('success_stories', [
            'name' => 'John Doe',
            'role' => 'Software Engineer',
            'company' => 'Tech Corp',
        ]);
    });

    test('admin can view edit success story page', function () {
        $story = SuccessStory::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/success-stories/{$story->id}/edit");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/SuccessStories/Edit')
            ->has('story')
        );
    });

    test('admin can update success story', function () {
        $story = SuccessStory::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/success-stories/{$story->id}", [
            'name' => 'Jane Doe Updated',
            'role' => 'Product Manager',
            'company' => 'New Corp',
            'story' => 'Updated success story content.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response->assertRedirect('/admin/success-stories');
        $this->assertDatabaseHas('success_stories', [
            'id' => $story->id,
            'name' => 'Jane Doe Updated',
            'company' => 'New Corp',
        ]);
    });

    test('admin can delete success story', function () {
        $story = SuccessStory::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/admin/success-stories/{$story->id}");

        $response->assertRedirect('/admin/success-stories');
        $this->assertDatabaseMissing('success_stories', ['id' => $story->id]);
    });

    test('admin can toggle success story status', function () {
        $story = SuccessStory::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->post("/admin/success-stories/{$story->id}/toggle-status");

        $response->assertRedirect();
        $this->assertDatabaseHas('success_stories', [
            'id' => $story->id,
            'is_active' => false,
        ]);
    });

    test('admin can bulk delete success stories', function () {
        $stories = SuccessStory::factory()->count(3)->create();
        $ids = $stories->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)->post('/admin/success-stories/bulk-destroy', [
            'ids' => $ids,
        ]);

        $response->assertRedirect();
        foreach ($ids as $id) {
            $this->assertDatabaseMissing('success_stories', ['id' => $id]);
        }
    });

    test('non-admin cannot access success stories admin', function () {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin/success-stories');

        $response->assertStatus(403);
    });

    test('success stories appear on home page', function () {
        $stories = SuccessStory::factory()->count(3)->create(['is_active' => true]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('successStories', 3)
        );
    });

    test('inactive stories do not appear on home page', function () {
        SuccessStory::factory()->count(2)->create(['is_active' => true]);
        SuccessStory::factory()->count(2)->create(['is_active' => false]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('successStories', 2)
        );
    });
});
