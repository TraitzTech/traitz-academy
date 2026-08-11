<?php

namespace Database\Factories;

use App\Models\Internship;
use App\Models\LogbookEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogbookEntry>
 */
class LogbookEntryFactory extends Factory
{
    protected $model = LogbookEntry::class;

    public function definition(): array
    {
        return [
            'internship_id' => Internship::factory(),
            'date' => now()->toDateString(),
            'content' => fake()->paragraph(),
            'hours_spent' => 8,
            'status' => LogbookEntry::STATUS_DRAFT,
        ];
    }

    public function submitted(): static
    {
        return $this->state(['status' => LogbookEntry::STATUS_SUBMITTED, 'submitted_at' => now()]);
    }
}
