<?php

namespace Database\Factories;

use App\Models\CommunityMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityMember>
 */
class CommunityMemberFactory extends Factory
{
    protected $model = CommunityMember::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'school' => fake()->randomElement(['University of Buea', 'ICT University', 'UBa', 'Catholic University']),
            'current_status' => CommunityMember::STATUS_STUDENT,
            'source' => CommunityMember::SOURCE_JOIN_FORM,
            'membership_status' => CommunityMember::MEMBERSHIP_MEMBER,
            'lifecycle_status' => CommunityMember::LIFECYCLE_ACTIVE,
            'directory_opt_in' => false,
            'email_opt_in' => true,
            'joined_at' => now(),
        ];
    }

    public function inDirectory(): static
    {
        return $this->state(fn () => ['directory_opt_in' => true]);
    }

    public function optedOut(): static
    {
        return $this->state(fn () => ['email_opt_in' => false]);
    }
}
