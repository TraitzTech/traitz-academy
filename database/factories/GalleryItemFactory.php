<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GalleryItem>
 */
class GalleryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->words(3, true));

        return [
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numerify('##'),
            'type' => fake()->randomElement(['image', 'youtube_video']),
            'image_path' => 'gallery/'.fake()->uuid().'.jpg',
            'youtube_url' => null,
            'description' => fake()->sentence(16),
            'tags' => [fake()->word(), fake()->word()],
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }

    public function youtubeVideo(): static
    {
        return $this->state(fn () => [
            'type' => 'youtube_video',
            'image_path' => null,
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
    }
}
