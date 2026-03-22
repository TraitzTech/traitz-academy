<?php

use App\Models\GalleryItem;
use App\Models\LearningResource;
use Inertia\Testing\AssertableInertia as Assert;

test('gallery and resources have separate public index pages', function () {
    GalleryItem::factory()->create(['title' => 'Bootcamp Photo']);
    LearningResource::factory()->create(['title' => 'Laravel Handbook']);

    $this->get(route('gallery.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gallery/Index')
            ->where('items.data.0.title', 'Bootcamp Photo')
        );

    $this->get(route('resources.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Resources/Index')
            ->where('resources.data.0.title', 'Laravel Handbook')
        );
});

test('inactive gallery and resources are not publicly viewable', function () {
    $galleryItem = GalleryItem::factory()->create(['is_active' => false]);
    $resource = LearningResource::factory()->create(['is_active' => false]);

    $this->get(route('gallery.show', $galleryItem))->assertNotFound();
    $this->get(route('resources.show', $resource))->assertNotFound();
});
