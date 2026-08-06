<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discussion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'parent_id',
        'body',
        'is_accepted_answer',
        'upvotes_count',
    ];

    protected function casts(): array
    {
        return [
            'is_accepted_answer' => 'boolean',
            'upvotes_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function upvotes(): HasMany
    {
        return $this->hasMany(DiscussionUpvote::class);
    }

    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
    }

    public function isReply(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeForLesson($query, int $lessonId)
    {
        return $query->where('lesson_id', $lessonId);
    }
}
