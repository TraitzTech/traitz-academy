<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TacTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'icon',
        'accent_color',
        'cover_image',
        'sort_order',
        'is_active',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $track) {
            if (blank($track->slug)) {
                $track->slug = static::uniqueSlug($track->name, $track->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'track';
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

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(CommunityMember::class, 'community_member_track')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function leaders(): HasMany
    {
        return $this->hasMany(TacLeader::class);
    }

    public function mentors(): HasMany
    {
        return $this->hasMany(TacLeader::class)
            ->where('role_type', TacLeader::ROLE_TRACK_MENTOR)
            ->where('is_active', true);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TacActivity::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
