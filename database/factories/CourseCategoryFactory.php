<?php

namespace Database\Factories;

use App\Models\CourseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CourseCategory>
 */
class CourseCategoryFactory extends Factory
{
    protected $model = CourseCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->sentence(),
            'icon' => '📚',
            'color' => fake()->hexColor(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
