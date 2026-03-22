<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SuccessStory>
 */
class SuccessStoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = [
            'Software Engineer',
            'Product Designer',
            'Data Analyst',
            'Web Developer',
            'Project Manager',
            'UI/UX Designer',
            'Full Stack Developer',
            'DevOps Engineer',
        ];

        $companies = [
            'Tech Startup Lagos',
            'Global Tech Company',
            'Finance Company',
            'Consulting Firm',
            'E-commerce Platform',
            'Digital Agency',
            'Healthcare Tech',
        ];

        $quotes = [
            'Traitz Academy transformed my career. The hands-on approach and mentorship made all the difference.',
            'The real-world projects and exposure to industry standards were invaluable.',
            'I went from zero to confident professional. The academy\'s structure is world-class.',
            'The skills I learned here opened doors I never thought possible.',
            'Best investment in my career. The instructors truly care about student success.',
            'The networking opportunities alone were worth it. I landed my dream job!',
        ];

        return [
            'name' => fake()->name(),
            'role' => fake()->randomElement($roles),
            'company' => fake()->randomElement($companies),
            'story' => fake()->randomElement($quotes),
            'image_url' => null,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    /**
     * Indicate that the story is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
