<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeedbackForm>
 */
class FeedbackFormFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->sentence(4, false);

        return [
            'title' => $title,
            'description' => $this->faker->paragraph(),
            'slug' => Str::slug($title).'-'.Str::random(6),
            'created_by' => User::factory(),
            'is_active' => true,
            'allow_anonymous' => true,
            'send_thank_you_email' => true,
            'closes_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function closed(): static
    {
        return $this->state(['closes_at' => now()->subDay()]);
    }
}
