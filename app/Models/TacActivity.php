<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TacActivity extends Model
{
    use HasFactory;

    public const TYPE_EVENT = 'event';

    public const TYPE_WORKSHOP = 'workshop';

    public const TYPE_TRAINING = 'training';

    public const TYPE_BOOTCAMP = 'bootcamp';

    public const TYPE_INTERNSHIP = 'internship';

    public const TYPE_HANDOUT = 'handout';

    public const TYPE_COMPETITION = 'competition';

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        self::TYPE_EVENT => 'Event',
        self::TYPE_WORKSHOP => 'Workshop',
        self::TYPE_TRAINING => 'Training',
        self::TYPE_BOOTCAMP => 'Bootcamp',
        self::TYPE_INTERNSHIP => 'Internship',
        self::TYPE_HANDOUT => 'Handout',
        self::TYPE_COMPETITION => 'Competition',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'title',
        'slug',
        'type',
        'tac_track_id',
        'program_id',
        'summary',
        'description',
        'cover_image',
        'location_type',
        'location',
        'meeting_url',
        'starts_at',
        'ends_at',
        'timezone',
        'is_recurring',
        'recurrence',
        'parent_activity_id',
        'capacity',
        'registration_required',
        'registration_opens_at',
        'registration_closes_at',
        'is_paid',
        'price',
        'currency',
        'organizer_leader_id',
        'created_by',
        'status',
        'published_at',
        'is_featured',
        'outcome_summary',
        'highlights',
        'rsvp_count',
    ];

    /**
     * Model-level mirrors of the column defaults, so a freshly created
     * activity reports the right currency, timezone and flags without
     * needing a refresh from the database first.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => self::TYPE_EVENT,
        'location_type' => 'physical',
        'timezone' => 'Africa/Douala',
        'is_recurring' => false,
        'registration_required' => true,
        'is_paid' => false,
        'price' => 0,
        'currency' => 'XAF',
        'status' => self::STATUS_DRAFT,
        'is_featured' => false,
        'rsvp_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'published_at' => 'datetime',
            'is_recurring' => 'boolean',
            'registration_required' => 'boolean',
            'is_paid' => 'boolean',
            'is_featured' => 'boolean',
            'recurrence' => 'array',
            'highlights' => 'array',
            'capacity' => 'integer',
            'price' => 'integer',
            'rsvp_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $activity) {
            if (blank($activity->slug)) {
                $activity->slug = static::uniqueSlug($activity->title, $activity->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'activity';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(TacTrack::class, 'tac_track_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(TacLeader::class, 'organizer_leader_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_activity_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(self::class, 'parent_activity_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(TacActivityRsvp::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(TacActivityMedium::class)->orderBy('sort_order');
    }

    public function competitionCriteria(): HasMany
    {
        return $this->hasMany(TacCompetitionCriterion::class)->orderBy('sort_order');
    }

    public function competitionEntries(): HasMany
    {
        return $this->hasMany(TacCompetitionEntry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_COMPLETED]);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $q) {
                $q->where('starts_at', '>=', now())->orWhere('ends_at', '>=', now());
            });
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->published()
            ->whereNotNull('starts_at')
            ->where(function (Builder $q) {
                $q->where('ends_at', '<', now())
                    ->orWhere(fn (Builder $inner) => $inner->whereNull('ends_at')->where('starts_at', '<', now()));
            });
    }

    /**
     * Activities a given leader may manage: TAC executives see everything,
     * track mentors see their track, school leads see what they organise.
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->canAccessAdminPanel()) {
            return $query;
        }

        $leaders = TacLeader::query()->active()->where('user_id', $user->id)->get();

        if ($leaders->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        if ($leaders->contains(fn (TacLeader $leader) => $leader->hasTacExecutiveAuthority())) {
            return $query;
        }

        $trackIds = $leaders->pluck('tac_track_id')->filter()->all();
        $leaderIds = $leaders->pluck('id')->all();

        return $query->where(function (Builder $q) use ($trackIds, $leaderIds) {
            $q->whereIn('organizer_leader_id', $leaderIds);

            if ($trackIds !== []) {
                $q->orWhereIn('tac_track_id', $trackIds);
            }
        });
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? ucfirst((string) $this->type);
    }

    public function isCompetition(): bool
    {
        return $this->type === self::TYPE_COMPETITION;
    }

    public function isPast(): bool
    {
        $end = $this->ends_at ?? $this->starts_at;

        return $end !== null && $end->isPast();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->rsvp_count >= $this->capacity;
    }

    public function seatsLeft(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->rsvp_count);
    }

    /**
     * Whether someone can RSVP right now, and if not, why.
     */
    public function registrationState(): array
    {
        if (! $this->registration_required) {
            return ['open' => false, 'reason' => 'no_registration'];
        }

        if ($this->status === self::STATUS_CANCELLED) {
            return ['open' => false, 'reason' => 'cancelled'];
        }

        if ($this->status !== self::STATUS_PUBLISHED) {
            return ['open' => false, 'reason' => 'unpublished'];
        }

        if ($this->registration_opens_at && now()->lt($this->registration_opens_at)) {
            return ['open' => false, 'reason' => 'not_yet_open'];
        }

        if ($this->registration_closes_at && now()->gt($this->registration_closes_at)) {
            return ['open' => false, 'reason' => 'closed'];
        }

        if ($this->isPast()) {
            return ['open' => false, 'reason' => 'past'];
        }

        if ($this->isFull()) {
            return ['open' => false, 'reason' => 'full'];
        }

        return ['open' => true, 'reason' => null];
    }

    public function syncRsvpCount(): void
    {
        $this->forceFill([
            'rsvp_count' => $this->rsvps()
                ->whereNotIn('status', [TacActivityRsvp::STATUS_CANCELLED])
                ->count(),
        ])->save();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
