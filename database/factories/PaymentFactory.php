<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'user_id' => User::factory(),
            'recorded_by' => null,
            'program_id' => Program::factory(),
            'reference' => 'PAY-'.strtoupper(fake()->bothify('###??##??')),
            'receipt_number' => 'RCT-'.fake()->numerify('######'),
            'mesomb_transaction_id' => fake()->uuid(),
            'payer_phone' => '67'.fake()->numerify('######'),
            'provider' => fake()->randomElement(['MTN', 'ORANGE']),
            'payment_channel' => fake()->randomElement(['ONLINE', 'ONSITE']),
            'amount' => fake()->randomFloat(2, 1000, 250000),
            'currency' => 'XAF',
            'payment_type' => fake()->randomElement(['full', 'installment']),
            'installment_number' => 1,
            'total_installments' => 1,
            'status' => fake()->randomElement(['pending', 'successful', 'failed']),
            'manual_entry' => false,
            'failure_reason' => null,
            'admin_notes' => null,
            'raw_response' => ['mocked' => true],
            'paid_at' => now(),
        ];
    }
}
