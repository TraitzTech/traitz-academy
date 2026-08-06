<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseLesson>
 */
class CourseLessonFactory extends Factory
{
    protected $model = CourseLesson::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'course_section_id' => CourseSection::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'type' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
            'duration' => '5:30',
            'is_free' => false,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }

    public function free(): static
    {
        return $this->state(['is_free' => true]);
    }

    public function quiz(): static
    {
        return $this->state(['type' => 'quiz', 'video_url' => null]);
    }

    public function text(): static
    {
        return $this->state(['type' => 'text', 'video_url' => null, 'content' => fake()->paragraph()]);
    }
}
