<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeedbackAnswer>
 */
class FeedbackAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feedback_response_id' => \App\Models\FeedbackResponse::factory(),
            'feedback_question_id' => \App\Models\FeedbackQuestion::factory(),
            'answer' => $this->faker->sentence(),
        ];
    }
}
