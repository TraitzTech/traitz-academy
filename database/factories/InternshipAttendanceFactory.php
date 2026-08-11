<?php

namespace Database\Factories;

use App\Models\Internship;
use App\Models\InternshipAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternshipAttendance>
 */
class InternshipAttendanceFactory extends Factory
{
    protected $model = InternshipAttendance::class;

    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'date' => now()->toDateString(),
            'clock_in_at' => now(),
            'status' => InternshipAttendance::STATUS_PRESENT,
            'source' => InternshipAttendance::SOURCE_SELF,
        ];
    }
}
