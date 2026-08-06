<?php

namespace Database\Factories;

use App\Models\LiveClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LiveClass>
 */
class LiveClassFactory extends Factory
{
    protected $model = LiveClass::class;

    public function definition(): array
    {
        $tutor = User::factory()->state(['role' => User::ROLE_TUTOR]);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'tutor_id' => $tutor,
            'created_by' => $tutor,
            'start_time' => now()->addHour(),
            'duration' => 60,
            'room_name' => 'class_'.Str::random(10),
            'access_type' => 'custom',
        ];
    }
}
