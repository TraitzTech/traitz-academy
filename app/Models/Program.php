<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory;

    public const INTERNSHIP_CATEGORIES = ['academic-internship', 'professional-internship'];

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
        'is_cv_required',
        'image_url',
        'is_featured',
        'is_active',
        'capacity',
        'enrolled_count',
        'start_date',
        'end_date',
        'applications_open_at',
        'applications_close_at',
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
            'is_cv_required' => 'boolean',
            'max_installments' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'applications_open_at' => 'datetime',
            'applications_close_at' => 'datetime',
        ];
    }

    /**
     * Whether the program is currently accepting applications. Requires the
     * program to be active and (if a window is set) the current time to fall
     * within it. Blank window ends = unbounded on that side (rolling intake).
     */
    public function applicationsOpen(?\Carbon\CarbonInterface $now = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = $now ?? now();

        if ($this->applications_open_at !== null && $now->lessThan($this->applications_open_at)) {
            return false;
        }

        // Close date is inclusive of its whole day (…23:59:59).
        if ($this->applications_close_at !== null && $now->greaterThan($this->applications_close_at->copy()->endOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * 'open' | 'not_yet' | 'closed' — for surfacing the apply state in the UI.
     */
    public function applicationStatus(?\Carbon\CarbonInterface $now = null): string
    {
        $now = $now ?? now();

        if ($this->applications_open_at !== null && $now->lessThan($this->applications_open_at)) {
            return 'not_yet';
        }

        if (! $this->applicationsOpen($now)) {
            return 'closed';
        }

        return 'open';
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function cohorts(): BelongsToMany
    {
        return $this->belongsToMany(Cohort::class, 'cohort_program')
            ->withPivot('supervisor_id')
            ->withTimestamps();
    }

    /**
     * User IDs of interns in this program (across all cohorts), for
     * Assignment/LiveClass/Schedule audience resolution.
     */
    public function studentIds(): \Illuminate\Support\Collection
    {
        return $this->hasMany(Internship::class)->pluck('user_id');
    }

    /**
     * Whether this program is an internship (academic or professional).
     */
    public function isInternship(): bool
    {
        return in_array($this->category, self::INTERNSHIP_CATEGORIES, true);
    }

    public function scopeInternships($query)
    {
        return $query->whereIn('category', self::INTERNSHIP_CATEGORIES);
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
