<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiForgeOrderItem>
 */
class AiForgeOrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_forge_order_id' => \App\Models\AiForgeOrder::factory(),
            'ai_forge_swag_id' => \App\Models\AiForgeSwag::factory(),
            'variation' => 'M / Black',
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => 5000,
            'total_price' => 5000,
        ];
    }
}
