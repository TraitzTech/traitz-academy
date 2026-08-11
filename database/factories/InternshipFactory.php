<?php

namespace Database\Factories;

use App\Models\Internship;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Internship>
 */
class InternshipFactory extends Factory
{
    protected $model = Internship::class;

    public function definition(): array
    {
        return [
            'cohort_id' => null,
            'program_id' => Program::factory()->state(['category' => 'professional-internship']),
            'user_id' => User::factory(),
            'application_id' => null,
            'supervisor_id' => null,
            'start_date' => now()->subWeek()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'status' => Internship::STATUS_ACTIVE,
            'work_mode' => Internship::MODE_ONSITE,
        ];
    }
}
