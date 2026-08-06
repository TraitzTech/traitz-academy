<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'category_id',
        'title',
        'slug',
        'short_description',
        'description',
        'cover_image',
        'level',
        'status',
        'price',
        'sale_price',
        'max_installments',
        'duration',
        'is_featured',
        'enrolled_count',
        'rating',
        'review_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'max_installments' => 'integer',
            'rating' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class)->orderBy('sort_order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function instalmentPlans(): HasMany
    {
        return $this->hasMany(CourseInstalmentPlan::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function coursePayments(): HasMany
    {
        return $this->hasMany(CoursePayment::class);
    }

    public function liveClasses(): BelongsToMany
    {
        return $this->belongsToMany(LiveClass::class, 'live_class_courses');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LmsSchedule::class);
    }

    /**
     * The learner's access-granting enrollment for this course, if any.
     * Returns null for guests or learners without an active/completed enrollment.
     */
    public function enrollmentFor(?User $user): ?Enrollment
    {
        if ($user === null) {
            return null;
        }

        return $this->enrollments()
            ->where('user_id', $user->id)
            ->grantsAccess()
            ->first();
    }

    /**
     * Whether the given user currently holds an access-granting enrollment.
     */
    public function grantsAccessTo(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->enrollments()
            ->where('user_id', $user->id)
            ->grantsAccess()
            ->exists();
    }

    public function isFree(): bool
    {
        return $this->effectivePrice() <= 0;
    }

    /**
     * Amount charged for enrollment/checkout. Null sale_price uses base price;
     * sale_price of 0 is treated as "no sale" (falls back to base price), not free.
     */
    public function effectivePrice(): float
    {
        $price = (float) $this->price;

        if ($this->sale_price === null) {
            return max(0.0, round($price, 2));
        }

        $sale = (float) $this->sale_price;

        if ($sale <= 0) {
            return max(0.0, round($price, 2));
        }

        return max(0.0, round(min($price, $sale), 2));
    }

    /**
     * Same idea as program fees: total course price split across up to {@see max_installments} payments.
     */
    public function installmentAmount(): float
    {
        $total = $this->effectivePrice();
        $max = max(1, (int) ($this->max_installments ?? 1));

        return $total > 0 ? round($total / $max, 2) : 0.0;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
