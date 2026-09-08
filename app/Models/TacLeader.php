<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TacLeader extends Model
{
    use HasFactory;

    public const ROLE_LEAD = 'lead';

    public const ROLE_CO_LEAD = 'co_lead';

    public const ROLE_SECRETARY = 'secretary';

    public const ROLE_TECHNICAL_LEAD = 'technical_lead';

    public const ROLE_TRACK_MENTOR = 'track_mentor';

    public const ROLE_SCHOOL_LEAD = 'school_lead';

    public const ROLE_PARTNERSHIP_LEAD = 'partnership_lead';

    /**
     * Role types in the order the public Team page should group them.
     *
     * @var array<string, string>
     */
    public const ROLE_LABELS = [
        self::ROLE_LEAD => 'Lead',
        self::ROLE_CO_LEAD => 'Co-Lead',
        self::ROLE_SECRETARY => 'Secretary',
        self::ROLE_TECHNICAL_LEAD => 'Technical Lead',
        self::ROLE_TRACK_MENTOR => 'Track Mentor',
        self::ROLE_SCHOOL_LEAD => 'School Lead',
        self::ROLE_PARTNERSHIP_LEAD => 'Partnership Lead',
    ];

    /**
     * Roles that carry full TAC administrative authority.
     */
    public const EXECUTIVE_ROLES = [
        self::ROLE_LEAD,
        self::ROLE_CO_LEAD,
        self::ROLE_SECRETARY,
        self::ROLE_TECHNICAL_LEAD,
    ];

    protected $fillable = [
        'user_id',
        'community_member_id',
        'name',
        'photo_path',
        'role_type',
        'slug',
        'role_title',
        'tac_track_id',
        'school',
        'bio',
        'email',
        'phone',
        'social_links',
        'started_on',
        'ended_on',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
        'sort_order' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $leader) {
            if (blank($leader->slug)) {
                $leader->slug = static::uniqueSlug($leader->name, $leader->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'leader';
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

    // Deliberately no getRouteKeyName() override: admin routes bind {leader}
    // by id (e.g. /admin/community/leaders/{leader}/retire), while the one
    // public profile route opts into slug binding explicitly via
    // `{leader:slug}` — overriding this globally would 404 every admin
    // route that passes a numeric id instead of a slug.

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'started_on' => 'date',
            'ended_on' => 'date',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CommunityMember::class, 'community_member_id');
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(TacTrack::class, 'tac_track_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TacActivity::class, 'organizer_leader_id');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(TacPartner::class, 'partnership_lead_id');
    }

    public function responsibilities(): HasMany
    {
        return $this->hasMany(TacLeaderResponsibility::class);
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(TacLeaderPerformanceReview::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('ended_on');
    }

    /**
     * Retired leaders — the "alumni leaders" timeline the spec asks for.
     */
    public function scopeRetired(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('is_active', false)->orWhereNotNull('ended_on'));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function roleLabel(): string
    {
        return $this->role_title ?: (self::ROLE_LABELS[$this->role_type] ?? ucfirst(str_replace('_', ' ', (string) $this->role_type)));
    }

    public function hasTacExecutiveAuthority(): bool
    {
        return in_array($this->role_type, self::EXECUTIVE_ROLES, true);
    }
}
