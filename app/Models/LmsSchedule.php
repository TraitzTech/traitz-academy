<?php

namespace App\Models;

use App\Concerns\HasAttachable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LmsSchedule extends Model
{
    use HasAttachable;

    /** @use HasFactory<\Database\Factories\LmsScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by',
        'course_id',
        'attachable_type',
        'attachable_id',
        'title',
        'description',
        'audience',
        'location',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function selectedStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lms_schedule_student', 'lms_schedule_id', 'student_id')
            ->withTimestamps();
    }
}
