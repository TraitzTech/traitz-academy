<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'instructor_id' => User::factory()->state(['role' => User::ROLE_TUTOR]),
            'category_id' => CourseCategoryFactory::new(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 1000000),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'level' => 'beginner',
            'status' => 'published',
            'price' => 0,
            'published_at' => now(),
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published', 'published_at' => now()]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function paid(float $price = 50000): static
    {
        return $this->state(['price' => $price]);
    }
}
