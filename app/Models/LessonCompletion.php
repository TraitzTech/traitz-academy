<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonCompletion extends Model
{
    /** @use HasFactory<\Database\Factories\LessonCompletionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_lesson_id',
        'enrollment_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
