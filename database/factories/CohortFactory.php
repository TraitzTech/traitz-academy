<?php

namespace Database\Factories;

use App\Models\Cohort;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cohort>
 */
class CohortFactory extends Factory
{
    protected $model = Cohort::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory()->state(['category' => 'professional-internship']),
            'supervisor_id' => null,
            'name' => fake()->words(2, true).' Cohort',
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'capacity' => null,
            'status' => Cohort::STATUS_ACTIVE,
            'timezone' => 'UTC',
        ];
    }
}
