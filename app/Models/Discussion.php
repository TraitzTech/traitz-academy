<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    /** @use HasFactory<\Database\Factories\DiscussionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'course_lesson_id',
        'title',
        'body',
        'is_pinned',
        'is_resolved',
        'replies_count',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned'    => 'boolean',
            'is_resolved'  => 'boolean',
            'replies_count' => 'integer',
        ];
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }
}
