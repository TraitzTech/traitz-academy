<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiForgeEvent extends Model
{
    /** @use HasFactory<\Database\Factories\AiForgeEventFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'tagline',
        'description',
        'short_description',
        'start_date',
        'end_date',
        'location',
        'is_online',
        'event_url',
        'capacity',
        'hero_image',
        'logo_image',
        'benefits',
        'schedule',
        'sponsors',
        'faqs',
        'stats',
        'is_active',
        'registration_open',
        'registration_fee',
        'early_bird_fee',
        'early_bird_deadline',
        'currency',
        'swag_store_active',
        'registration_note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_online' => 'boolean',
            'is_active' => 'boolean',
            'registration_open' => 'boolean',
            'registration_fee' => 'integer',
            'early_bird_fee' => 'integer',
            'early_bird_deadline' => 'date',
            'swag_store_active' => 'boolean',
            'benefits' => 'array',
            'schedule' => 'array',
            'sponsors' => 'array',
            'faqs' => 'array',
            'stats' => 'array',
        ];
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(AiForgeRegistration::class);
    }

    public function swags(): HasMany
    {
        return $this->hasMany(AiForgeSwag::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(AiForgeOrder::class);
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->where($field ?? 'slug', $value)->firstOrFail();
    }
}
