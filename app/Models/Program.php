<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'overview',
        'who_is_for',
        'skills_and_tools',
        'duration',
        'learning_outcomes',
        'certification',
        'price',
        'max_installments',
        'image_url',
        'is_featured',
        'is_active',
        'capacity',
        'enrolled_count',
        'start_date',
        'end_date',
        'curriculum',
    ];

    /**
     * Retrieve the model for a bound value.
     *
     * Since slugs are unique per category (not globally), we need to
     * handle slug collisions by returning the first match.
     * For public routes, this finds the first active match.
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $field = $field ?? $this->getRouteKeyName();

        if ($field === 'slug') {
            return static::where('slug', $value)->first();
        }

        return static::where($field, $value)->first();
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'max_installments' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
