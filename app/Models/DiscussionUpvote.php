<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscussionUpvote extends Model
{
    /** @use HasFactory<\Database\Factories\DiscussionUpvoteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'discussion_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discussion(): BelongsTo
    {
        return $this->belongsTo(Discussion::class);
    }
}
