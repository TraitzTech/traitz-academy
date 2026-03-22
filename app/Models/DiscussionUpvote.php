<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DiscussionUpvote extends Model
{
    /** @use HasFactory<\Database\Factories\DiscussionUpvoteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'upvotable_id',
        'upvotable_type',
    ];

    public function upvotable(): MorphTo
    {
        return $this->morphTo();
    }
}
