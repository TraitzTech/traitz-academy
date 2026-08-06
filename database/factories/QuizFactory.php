<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'lesson_id' => null,
            'title' => fake()->sentence(3),
            'instructions' => fake()->sentence(),
            'pass_mark_percentage' => 60,
            'max_attempts' => null,
            'is_required' => false,
            'reveal_answers' => true,
        ];
    }
}
