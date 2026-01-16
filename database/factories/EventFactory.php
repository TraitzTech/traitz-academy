<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'title' => ucfirst($title),
            'slug' => str(ucfirst($title))->slug(),
            'description' => fake()->paragraph(3),
            'event_date' => fake()->dateTimeBetween('now', '+6 months'),
            'location' => fake()->city() . ', ' . fake()->country(),
            'is_online' => fake()->boolean(40),
            'event_url' => fake()->boolean(40) ? fake()->url() : null,
            'capacity' => fake()->randomElement([50, 100, 200, 500]),
            'registered_count' => fake()->numberBetween(0, 100),
            'category' => fake()->randomElement(['workshop', 'webinar', 'meetup', 'conference']),
            'image_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400',
            'is_active' => true,
            'agenda' => fake()->paragraph(4),
        ];
    }
}
