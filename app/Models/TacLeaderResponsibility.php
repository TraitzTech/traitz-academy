<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A duty academy staff has assigned to a specific TAC leader — what they are
 * actually accountable for, beyond the generic expectations of their role.
 */
class TacLeaderResponsibility extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_COMPLETED => 'Completed',
    ];

    protected $fillable = [
        'tac_leader_id',
        'title',
        'description',
        'status',
        'due_date',
        'assigned_by',
        'sort_order',
        'completed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(TacLeader::class, 'tac_leader_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_COMPLETED
            && $this->due_date !== null
            && $this->due_date->isPast();
    }
}
