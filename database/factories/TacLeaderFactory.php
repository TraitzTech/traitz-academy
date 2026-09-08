<?php

namespace Database\Factories;

use App\Models\TacLeader;
use App\Models\TacTrack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TacLeader>
 */
class TacLeaderFactory extends Factory
{
    protected $model = TacLeader::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role_type' => TacLeader::ROLE_TRACK_MENTOR,
            'tac_track_id' => TacTrack::factory(),
            'email' => fake()->unique()->safeEmail(),
            'bio' => fake()->paragraph(),
            'started_on' => now()->subMonths(3)->toDateString(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function role(string $roleType): static
    {
        return $this->state(fn () => [
            'role_type' => $roleType,
            'tac_track_id' => $roleType === TacLeader::ROLE_TRACK_MENTOR ? TacTrack::factory() : null,
            'school' => $roleType === TacLeader::ROLE_SCHOOL_LEAD ? 'University of Buea' : null,
        ]);
    }

    public function retired(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
            'ended_on' => now()->subMonth()->toDateString(),
        ]);
    }
}
