<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionUpvote extends Model
{
    /** @use HasFactory<\Database\Factories\DiscussionUpvoteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'discussion_id',
    ];
}
