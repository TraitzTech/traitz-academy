<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interview>
 */
class InterviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'program_id' => \App\Models\Program::factory(),
            'created_by' => \App\Models\User::factory(),
            'passing_score' => 70,
            'time_limit_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'is_active' => true,
        ];
    }
}
