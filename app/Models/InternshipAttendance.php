<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipAttendance extends Model
{
    /** @use HasFactory<\Database\Factories\InternshipAttendanceFactory> */
    use HasFactory;

    protected $table = 'internship_attendance';

    public const STATUS_PRESENT = 'present';

    public const STATUS_LATE = 'late';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_EXCUSED = 'excused';

    public const SOURCE_SELF = 'self';

    public const SOURCE_SUPERVISOR = 'supervisor';

    public const SOURCE_SYSTEM = 'system';

    protected $fillable = [
        'internship_id',
        'date',
        'clock_in_at',
        'clock_out_at',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_distance_m',
        'hours',
        'status',
        'source',
        'note',
        'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'clock_in_latitude' => 'decimal:7',
            'clock_in_longitude' => 'decimal:7',
            'clock_in_distance_m' => 'integer',
            'hours' => 'decimal:2',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function isOpen(): bool
    {
        return $this->clock_in_at !== null && $this->clock_out_at === null;
    }
}
