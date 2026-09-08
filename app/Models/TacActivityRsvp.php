<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacActivityRsvp extends Model
{
    use HasFactory;

    public const STATUS_REGISTERED = 'registered';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_WAITLISTED = 'waitlisted';

    public const STATUS_ATTENDED = 'attended';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::STATUS_REGISTERED => 'Registered',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_WAITLISTED => 'Waitlisted',
        self::STATUS_ATTENDED => 'Attended',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_NO_SHOW => 'No show',
    ];

    public const PAYMENT_FREE = 'free';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_REFUNDED = 'refunded';

    protected $fillable = [
        'tac_activity_id',
        'community_member_id',
        'status',
        'payment_status',
        'amount',
        'currency',
        'payment_reference',
        'payment_phone',
        'paid_at',
        'note',
        'checked_in_at',
        'reminded_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_REGISTERED,
        'payment_status' => self::PAYMENT_FREE,
        'amount' => 0,
        'currency' => 'XAF',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'reminded_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(TacActivity::class, 'tac_activity_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CommunityMember::class, 'community_member_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED]);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * A paid RSVP only counts as a real seat once payment clears.
     */
    public function isSettled(): bool
    {
        return in_array($this->payment_status, [self::PAYMENT_FREE, self::PAYMENT_PAID], true);
    }
}
