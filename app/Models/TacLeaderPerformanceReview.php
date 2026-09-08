<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A staff-written performance review for a TAC leader — a rating plus notes
 * for a given period. Written by academy staff, visible to the leader on
 * their own dashboard.
 */
class TacLeaderPerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'tac_leader_id',
        'rating',
        'period_label',
        'notes',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(TacLeader::class, 'tac_leader_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->latest();
    }
}
