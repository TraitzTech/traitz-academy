<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'payment_type' => 'free',
            'access_status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(['access_status' => 'suspended']);
    }

    public function revoked(): static
    {
        return $this->state(['access_status' => 'revoked']);
    }

    public function completed(): static
    {
        return $this->state(['access_status' => 'completed', 'progress' => 100, 'completed_at' => now()]);
    }
}
