<?php

namespace Database\Factories;

use App\Models\CourseLesson;
use App\Models\LessonVideoProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonVideoProgress>
 */
class LessonVideoProgressFactory extends Factory
{
    protected $model = LessonVideoProgress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_lesson_id' => CourseLesson::factory(),
            'watched_seconds' => 100,
            'duration_seconds' => 100,
            'percentage' => 100,
            'last_watched_at' => now(),
        ];
    }
}
