<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeedbackQuestion>
 */
class FeedbackQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['text', 'multiple_choice']);

        return [
            'feedback_form_id' => \App\Models\FeedbackForm::factory(),
            'question' => $this->faker->sentence().'?',
            'type' => $type,
            'options' => $type === 'multiple_choice'
                ? $this->faker->randomElements(['Excellent', 'Good', 'Average', 'Poor', 'Very Poor'], 4)
                : null,
            'required' => true,
            'sort_order' => 0,
        ];
    }
}
