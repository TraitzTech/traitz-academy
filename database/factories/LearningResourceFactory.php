<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearningResource>
 */
class LearningResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->words(4, true));

        return [
            'title' => $title,
            'slug' => str($title)->slug().'-'.fake()->unique()->numerify('##'),
            'type' => 'writing',
            'description' => fake()->sentence(20),
            'document_path' => null,
            'youtube_url' => null,
            'external_url' => null,
            'content' => fake()->paragraphs(3, true),
            'tags' => [fake()->word(), fake()->word()],
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
            'published_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }

    public function document(): static
    {
        return $this->state(fn () => [
            'type' => 'document',
            'document_path' => 'resources/'.fake()->uuid().'.pdf',
            'content' => null,
        ]);
    }

    public function youtubeVideo(): static
    {
        return $this->state(fn () => [
            'type' => 'youtube_video',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'content' => null,
        ]);
    }
}
