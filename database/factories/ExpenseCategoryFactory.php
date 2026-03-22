<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Office Supplies',
            'Transportation',
            'Meals & Catering',
            'Venue Rental',
            'Equipment',
            'Software & Subscriptions',
            'Staff Salaries',
            'Marketing',
            'Utilities',
            'Training Materials',
            'Miscellaneous',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'is_active' => true,
        ];
    }
}
