<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeedbackResponse>
 */
class FeedbackResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feedback_form_id' => \App\Models\FeedbackForm::factory(),
            'user_id' => null,
            'is_anonymous' => false,
            'respondent_name' => $this->faker->name(),
            'respondent_email' => $this->faker->safeEmail(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    public function anonymous(): static
    {
        return $this->state([
            'is_anonymous' => true,
            'respondent_name' => null,
            'respondent_email' => null,
        ]);
    }
}
