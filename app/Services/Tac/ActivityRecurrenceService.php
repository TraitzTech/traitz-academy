<?php

namespace App\Services\Tac;

use App\Models\TacActivity;
use Illuminate\Support\Carbon;

/**
 * Expands a recurring activity (e.g. "a workshop every second Saturday, eight
 * times") into concrete child occurrences.
 *
 * Occurrences are real rows, not computed on the fly, so each one can carry its
 * own RSVPs, capacity, cover image and outcome write-up — which is what makes
 * a long-running series legible in the archive years later.
 */
class ActivityRecurrenceService
{
    public const MAX_OCCURRENCES = 52;

    /**
     * Generate the occurrences described by the parent's recurrence rule.
     * Existing occurrences are left alone; only missing dates are created.
     *
     * @return int the number of occurrences created
     */
    public function generate(TacActivity $activity): int
    {
        if (! $activity->is_recurring || $activity->parent_activity_id !== null) {
            return 0;
        }

        $dates = $this->occurrenceDates($activity);

        if ($dates === []) {
            return 0;
        }

        $existing = $activity->occurrences()->pluck('starts_at')
            ->map(fn ($date) => $date?->toDateTimeString())
            ->filter()
            ->all();

        $duration = $activity->starts_at && $activity->ends_at
            ? $activity->starts_at->diffInMinutes($activity->ends_at)
            : null;

        $created = 0;

        foreach ($dates as $index => $date) {
            if (in_array($date->toDateTimeString(), $existing, true)) {
                continue;
            }

            TacActivity::create([
                'title' => $activity->title.' — #'.($index + 2),
                'type' => $activity->type,
                'tac_track_id' => $activity->tac_track_id,
                'program_id' => $activity->program_id,
                'summary' => $activity->summary,
                'description' => $activity->description,
                'cover_image' => $activity->cover_image,
                'location_type' => $activity->location_type,
                'location' => $activity->location,
                'meeting_url' => $activity->meeting_url,
                'starts_at' => $date,
                'ends_at' => $duration === null ? null : $date->copy()->addMinutes($duration),
                'timezone' => $activity->timezone,
                'parent_activity_id' => $activity->id,
                'capacity' => $activity->capacity,
                'registration_required' => $activity->registration_required,
                'is_paid' => $activity->is_paid,
                'price' => $activity->price,
                'currency' => $activity->currency,
                'organizer_leader_id' => $activity->organizer_leader_id,
                'created_by' => $activity->created_by,
                'status' => $activity->status,
                'published_at' => $activity->published_at,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * The dates after the parent's own start date.
     *
     * @return array<int, Carbon>
     */
    protected function occurrenceDates(TacActivity $activity): array
    {
        $start = $activity->starts_at;
        $rule = $activity->recurrence ?? [];
        $frequency = $rule['frequency'] ?? null;
        $count = (int) ($rule['count'] ?? 0);

        if (! $start || ! $frequency || $count < 2) {
            return [];
        }

        $count = min($count, self::MAX_OCCURRENCES);
        $dates = [];
        $cursor = Carbon::parse($start);

        // The parent row is occurrence #1, so we generate count-1 children.
        for ($i = 1; $i < $count; $i++) {
            $cursor = match ($frequency) {
                'weekly' => $cursor->copy()->addWeek(),
                'biweekly' => $cursor->copy()->addWeeks(2),
                'monthly' => $cursor->copy()->addMonthNoOverflow(),
                default => null,
            };

            if ($cursor === null) {
                break;
            }

            $dates[] = $cursor;
        }

        return $dates;
    }
}
