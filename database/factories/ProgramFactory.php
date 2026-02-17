<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['professional-training', 'bootcamp', 'workshop', 'academic-internship', 'professional-internship'];
        $category = fake()->randomElement($categories);
        $title = fake()->words(3, true);
        $price = fake()->randomElement([0, 50000, 100000, 150000, 200000]);

        return [
            'title' => ucfirst($title),
            'slug' => str(ucfirst($title))->slug(),
            'category' => $category,
            'description' => fake()->paragraph(3),
            'overview' => fake()->paragraph(4),
            'who_is_for' => fake()->paragraph(2),
            'skills_and_tools' => implode(', ', fake()->words(8)),
            'duration' => fake()->randomElement(['4 weeks', '8 weeks', '12 weeks', '6 months', '3 months']),
            'learning_outcomes' => implode('. ', fake()->sentences(5)).'.',
            'certification' => 'Industry-recognized certificate of completion',
            'price' => $price,
            'max_installments' => $price > 0 ? fake()->numberBetween(1, 4) : 1,
            'image_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&h=400',
            'is_featured' => fake()->boolean(30),
            'is_active' => true,
            'capacity' => fake()->randomElement([20, 30, 50, 100]),
            'enrolled_count' => fake()->numberBetween(0, 100),
            'start_date' => now()->addDays(fake()->numberBetween(1, 60)),
            'end_date' => now()->addDays(fake()->numberBetween(61, 180)),
            'curriculum' => fake()->paragraph(5),
        ];
    }
}
