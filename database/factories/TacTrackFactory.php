<?php

namespace Database\Factories;

use App\Models\TacTrack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TacTrack>
 */
class TacTrackFactory extends Factory
{
    protected $model = TacTrack::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'tagline' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'accent_color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
