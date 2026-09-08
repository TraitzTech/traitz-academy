<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notifiable;

/**
 * A member of the Traitz Academy Community (TAC).
 *
 * Membership is keyed by email and stands on its own: anyone who registers for
 * a program, event, AI Forge, course or internship is auto-included, whether or
 * not they ever create a login. When an account with the same email exists (or
 * is created later) the two records link and the member gets the member area.
 */
class CommunityMember extends Model
{
    use HasFactory, Notifiable;

    public const STATUS_STUDENT = 'student';

    public const STATUS_PAST_INTERN = 'past_intern';

    public const STATUS_TECH_ENTHUSIAST = 'tech_enthusiast';

    public const STATUS_PROFESSIONAL = 'professional';

    public const STATUS_OTHER = 'other';

    /** @var array<string, string> */
    public const CURRENT_STATUS_LABELS = [
        self::STATUS_STUDENT => 'Student',
        self::STATUS_PAST_INTERN => 'Past intern',
        self::STATUS_TECH_ENTHUSIAST => 'Tech enthusiast',
        self::STATUS_PROFESSIONAL => 'Working professional',
        self::STATUS_OTHER => 'Other',
    ];

    public const SOURCE_JOIN_FORM = 'join_form';

    public const SOURCE_PROGRAM_APPLICATION = 'program_application';

    public const SOURCE_EVENT = 'event';

    public const SOURCE_AI_FORGE = 'ai_forge';

    public const SOURCE_COURSE = 'course';

    public const SOURCE_INTERNSHIP = 'internship';

    public const SOURCE_ADMIN = 'admin';

    public const SOURCE_IMPORT = 'import';

    /** @var array<string, string> */
    public const SOURCE_LABELS = [
        self::SOURCE_JOIN_FORM => 'Joined directly',
        self::SOURCE_PROGRAM_APPLICATION => 'Program application',
        self::SOURCE_EVENT => 'Event registration',
        self::SOURCE_AI_FORGE => 'AI Forge',
        self::SOURCE_COURSE => 'Course enrollment',
        self::SOURCE_INTERNSHIP => 'Internship',
        self::SOURCE_ADMIN => 'Added by admin',
        self::SOURCE_IMPORT => 'Imported',
    ];

    public const MEMBERSHIP_MEMBER = 'member';

    public const MEMBERSHIP_CONTRIBUTOR = 'contributor';

    public const MEMBERSHIP_MENTOR = 'mentor';

    public const MEMBERSHIP_LEAD = 'lead';

    public const MEMBERSHIP_ALUMNI = 'alumni';

    /** @var array<string, string> */
    public const MEMBERSHIP_LABELS = [
        self::MEMBERSHIP_MEMBER => 'Member',
        self::MEMBERSHIP_CONTRIBUTOR => 'Contributor',
        self::MEMBERSHIP_MENTOR => 'Mentor',
        self::MEMBERSHIP_LEAD => 'Lead',
        self::MEMBERSHIP_ALUMNI => 'Alumni',
    ];

    public const LIFECYCLE_ACTIVE = 'active';

    public const LIFECYCLE_DORMANT = 'dormant';

    public const LIFECYCLE_UNSUBSCRIBED = 'unsubscribed';

    public const LIFECYCLE_BLOCKED = 'blocked';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'school',
        'current_status',
        'heard_about',
        'bio',
        'avatar_path',
        'social_links',
        'source',
        'sourceable_type',
        'sourceable_id',
        'membership_status',
        'lifecycle_status',
        'engagement_score',
        'directory_opt_in',
        'email_opt_in',
        'joined_at',
        'welcomed_at',
        'last_engaged_at',
        'admin_notes',
    ];

    /**
     * Model-level mirrors of the column defaults. Without these, a freshly
     * created member has these attributes unset until refreshed — which would
     * make `isMailable()` read false and silently swallow the welcome email.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'current_status' => self::STATUS_STUDENT,
        'source' => self::SOURCE_JOIN_FORM,
        'membership_status' => self::MEMBERSHIP_MEMBER,
        'lifecycle_status' => self::LIFECYCLE_ACTIVE,
        'engagement_score' => 0,
        'directory_opt_in' => false,
        'email_opt_in' => true,
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'directory_opt_in' => 'boolean',
            'email_opt_in' => 'boolean',
            'engagement_score' => 'integer',
            'joined_at' => 'datetime',
            'welcomed_at' => 'datetime',
            'last_engaged_at' => 'datetime',
        ];
    }

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The registration record that first brought this person into TAC.
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(TacTrack::class, 'community_member_track')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(TacActivityRsvp::class);
    }

    public function competitionEntries(): HasMany
    {
        return $this->hasMany(TacCompetitionEntry::class);
    }

    public function leadership(): HasOne
    {
        return $this->hasOne(TacLeader::class, 'community_member_id')->where('is_active', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('lifecycle_status', self::LIFECYCLE_ACTIVE);
    }

    /**
     * Members who may be shown in the member directory.
     */
    public function scopeInDirectory(Builder $query): Builder
    {
        return $query->active()->where('directory_opt_in', true);
    }

    /**
     * Members who may receive broadcast community email.
     */
    public function scopeMailable(Builder $query): Builder
    {
        return $query->where('email_opt_in', true)
            ->whereNotIn('lifecycle_status', [self::LIFECYCLE_UNSUBSCRIBED, self::LIFECYCLE_BLOCKED]);
    }

    /**
     * Narrow the roster to what a staff member is entitled to see: everything
     * for TAC executives, own-track members for a mentor, own-school members
     * for a school lead.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasTacExecutiveAuthority()) {
            return $query;
        }

        $trackIds = $user->tacManagedTrackIds();
        $schools = $user->tacManagedSchools();

        if ($trackIds === [] && $schools === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($trackIds, $schools) {
            if ($trackIds !== []) {
                $q->whereHas('tracks', fn (Builder $t) => $t->whereIn('tac_tracks.id', $trackIds));
            }

            if ($schools !== []) {
                $q->orWhereIn('school', $schools);
            }
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('school', 'like', "%{$term}%");
        });
    }

    public function statusLabel(): string
    {
        return self::CURRENT_STATUS_LABELS[$this->current_status] ?? 'Member';
    }

    public function sourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->source] ?? 'Unknown';
    }

    public function isMailable(): bool
    {
        return $this->email_opt_in
            && ! in_array($this->lifecycle_status, [self::LIFECYCLE_UNSUBSCRIBED, self::LIFECYCLE_BLOCKED], true);
    }

    /**
     * Bump engagement whenever the member does something meaningful (RSVP,
     * competition entry, profile update) so admins can segment by activity.
     */
    public function recordEngagement(int $points = 1): void
    {
        $this->forceFill([
            'engagement_score' => $this->engagement_score + $points,
            'last_engaged_at' => now(),
        ])->save();
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }
}
