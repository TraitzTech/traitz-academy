<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
            'upvotes_count'      => 'integer',
        ];
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
}
