<?php

namespace Database\Factories;

use App\Models\TacActivity;
use App\Models\TacTrack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TacActivity>
 */
class TacActivityFactory extends Factory
{
    protected $model = TacActivity::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);
        $starts = now()->addDays(fake()->numberBetween(3, 60));

        return [
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'type' => TacActivity::TYPE_WORKSHOP,
            'tac_track_id' => TacTrack::factory(),
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraphs(3, true),
            'location_type' => 'physical',
            'location' => 'Traitz Academy, Buea',
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHours(3),
            'timezone' => 'Africa/Douala',
            'registration_required' => true,
            'is_paid' => false,
            'price' => 0,
            'currency' => 'XAF',
            'status' => TacActivity::STATUS_PUBLISHED,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => TacActivity::STATUS_DRAFT, 'published_at' => null]);
    }

    public function past(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subMonth()->addHours(3),
        ]);
    }

    public function competition(): static
    {
        return $this->state(fn () => ['type' => TacActivity::TYPE_COMPETITION]);
    }

    public function paid(int $price = 5000): static
    {
        return $this->state(fn () => ['is_paid' => true, 'price' => $price]);
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(fn () => ['capacity' => $capacity]);
    }
}
