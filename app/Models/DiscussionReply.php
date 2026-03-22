<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionReply extends Model
{
    /** @use HasFactory<\Database\Factories\DiscussionReplyFactory> */
    use HasFactory;

    protected $fillable = [
        'discussion_id',
        'user_id',
        'body',
        'is_answer',
    ];

    protected function casts(): array
    {
        return [
            'is_answer' => 'boolean',
        ];
    }

    public function scopeAnswers($query)
    {
        return $query->where('is_answer', true);
    }
}
