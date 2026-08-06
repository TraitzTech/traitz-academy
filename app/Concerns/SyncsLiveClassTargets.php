<?php

namespace App\Concerns;

use App\Models\Cohort;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

/**
 * Shared target-sync logic for Admin/Tutor LiveClassController — writes the
 * polymorphic live_class_targets rows (Course/Cohort/Program), and mirrors
 * course-type targets into the legacy live_class_courses pivot so
 * LiveClass::courses() keeps working during the dual-write rollout window.
 */
trait SyncsLiveClassTargets
{
    private const TARGET_TYPES = [
        'course' => Course::class,
        'cohort' => Cohort::class,
        'program' => Program::class,
    ];

    /**
     * @param  array<int, array{type:string,id:int}>  $targets
     */
    private function syncTargets(LiveClass $liveClass, array $targets): void
    {
        DB::table('live_class_targets')->where('live_class_id', $liveClass->id)->delete();

        $courseIds = [];
        $now = now();
        $rows = [];

        foreach ($targets as $target) {
            $modelClass = self::TARGET_TYPES[$target['type']] ?? null;
            if (! $modelClass) {
                continue;
            }

            $rows[] = [
                'live_class_id' => $liveClass->id,
                'target_type' => $modelClass,
                'target_id' => (int) $target['id'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($modelClass === Course::class) {
                $courseIds[] = (int) $target['id'];
            }
        }

        if ($rows !== []) {
            DB::table('live_class_targets')->insert($rows);
        }

        $liveClass->courses()->sync($courseIds);
    }

    /**
     * @return array<int, array{type:string,id:int}>
     */
    private function targetRowsForDisplay(LiveClass $liveClass): array
    {
        $typeKeys = array_flip(self::TARGET_TYPES);

        return $liveClass->targetRows()
            ->map(fn ($row) => [
                'type' => $typeKeys[$row->target_type] ?? null,
                'id' => (int) $row->target_id,
            ])
            ->filter(fn ($row) => $row['type'] !== null)
            ->values()
            ->all();
    }
}
