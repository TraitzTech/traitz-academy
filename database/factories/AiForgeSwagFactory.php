<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AiForgeSwag>
 */
class AiForgeSwagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['t-shirt', 'cap', 'polo', 'water-bottle', 'hoodie', 'sticker-pack'];
        $category = fake()->randomElement($categories);
        $name = 'AI Forge '.ucfirst(str_replace('-', ' ', $category));

        return [
            'ai_forge_event_id' => \App\Models\AiForgeEvent::factory(),
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name.'-'.fake()->unique()->randomNumber(4)),
            'category' => $category,
            'description' => fake()->paragraph(),
            'price' => fake()->randomElement([2500, 3000, 5000, 7500, 10000]),
            'currency' => 'XAF',
            'stock_quantity' => fake()->numberBetween(10, 100),
            'sold_count' => 0,
            'sort_order' => 0,
            'is_active' => true,
            'is_featured' => fake()->boolean(30),
            'variations' => [
                ['type' => 'size', 'options' => ['S', 'M', 'L', 'XL', 'XXL']],
                ['type' => 'color', 'options' => ['Black', 'White', 'Navy']],
            ],
        ];
    }
}
