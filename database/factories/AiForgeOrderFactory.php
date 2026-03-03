<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiForgeOrder>
 */
class AiForgeOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_forge_event_id' => \App\Models\AiForgeEvent::factory(),
            'user_id' => null,
            'order_number' => 'AIF-'.now()->format('YmdHis').'-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6)),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'subtotal' => 5000,
            'surcharge_amount' => 100,
            'surcharge_percentage' => 2,
            'total_amount' => 5100,
            'currency' => 'XAF',
            'status' => 'pending',
            'payment_status' => 'pending',
        ];
    }
}
