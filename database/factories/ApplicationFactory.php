<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'bio' => fake()->paragraph(2),
            'education_level' => fake()->randomElement(['High School', 'Bachelor', 'Master', 'Doctorate']),
            'institution_name' => fake()->company(),
            'academic_duration' => fake()->randomElement(['1 semester', '2 semesters', '1 year', '2 years']),
            'motivation' => fake()->paragraph(3),
            'experience' => fake()->paragraph(2),
            'internship_letter_path' => null,
            'cv_path' => null,
            'status' => fake()->randomElement(['pending', 'accepted', 'rejected']),
            'application_type' => fake()->randomElement(['academic', 'professional', 'job']),
            'notes' => null,
            'reviewed_at' => null,
        ];
    }
}
