<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookEntry extends Model
{
    /** @use HasFactory<\Database\Factories\LogbookEntryFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_NEEDS_REVISION = 'needs_revision';

    protected $fillable = [
        'internship_id',
        'date',
        'content',
        'hours_spent',
        'learnings',
        'blockers',
        'status',
        'submitted_at',
        'supervisor_feedback',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours_spent' => 'decimal:1',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isEditableByIntern(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_NEEDS_REVISION], true);
    }
}
