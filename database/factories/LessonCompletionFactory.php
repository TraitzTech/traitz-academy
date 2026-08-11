<?php

namespace Database\Factories;

use App\Models\CourseLesson;
use App\Models\Enrollment;
use App\Models\LessonCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonCompletion>
 */
class LessonCompletionFactory extends Factory
{
    protected $model = LessonCompletion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_lesson_id' => CourseLesson::factory(),
            'enrollment_id' => Enrollment::factory(),
            'completed_at' => now(),
        ];
    }
}
