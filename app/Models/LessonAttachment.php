<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonAttachment extends Model
{
    /** @use HasFactory<\Database\Factories\LessonAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'course_lesson_id',
        'name',
        'file_url',
        'file_type',
        'file_size',
        'sort_order',
    ];

    protected $appends = [
        'formatted_file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 1).' '.$units[$unit];
    }
}
