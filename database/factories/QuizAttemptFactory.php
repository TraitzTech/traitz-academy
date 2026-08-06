<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'answers' => [],
            'status' => 'in_progress',
            'started_at' => now(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(['status' => 'submitted', 'submitted_at' => now()]);
    }

    public function graded(bool $passed = true): static
    {
        return $this->state([
            'status' => 'graded',
            'submitted_at' => now(),
            'graded_at' => now(),
            'passed' => $passed,
            'score_percentage' => $passed ? 80 : 30,
        ]);
    }
}
