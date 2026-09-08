<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TacPartner extends Model
{
    use HasFactory;

    /** @var array<string, string> */
    public const TIER_LABELS = [
        'platinum' => 'Platinum Partner',
        'gold' => 'Gold Partner',
        'silver' => 'Silver Partner',
        'academic' => 'Academic Partner',
        'community' => 'Community Partner',
    ];

    /**
     * Display order for tier groupings on the public Partners page.
     */
    public const TIER_ORDER = ['platinum', 'gold', 'silver', 'academic', 'community'];

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'website_url',
        'tier',
        'description',
        'contact_name',
        'contact_email',
        'contact_phone',
        'partnership_lead_id',
        'started_on',
        'ended_on',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'tier' => 'community',
        'is_active' => true,
        'is_featured' => false,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $partner) {
            if (blank($partner->slug)) {
                $partner->slug = static::uniqueSlug($partner->name, $partner->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'partner';
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

    public function partnershipLead(): BelongsTo
    {
        return $this->belongsTo(TacLeader::class, 'partnership_lead_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function tierLabel(): string
    {
        return self::TIER_LABELS[$this->tier] ?? 'Partner';
    }
}
