<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_category_id' => ExpenseCategory::factory(),
            'recorded_by' => User::factory(),
            'program_id' => fake()->boolean(40) ? Program::factory() : null,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'amount' => fake()->randomFloat(2, 500, 500000),
            'currency' => 'XAF',
            'payment_method' => fake()->randomElement(['cash', 'mobile_money', 'bank_transfer', 'cheque']),
            'receipt_reference' => 'EXP-'.strtoupper(fake()->bothify('###??##??')),
            'vendor' => fake()->optional()->company(),
            'expense_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'status' => 'approved',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
