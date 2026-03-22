<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FeedbackForm extends Model
{
    /** @use HasFactory<\Database\Factories\FeedbackFormFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'created_by',
        'is_active',
        'allow_anonymous',
        'send_thank_you_email',
        'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allow_anonymous' => 'boolean',
            'send_thank_you_email' => 'boolean',
            'closes_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FeedbackForm $form) {
            if (empty($form->slug)) {
                $form->slug = Str::slug($form->title).'-'.Str::random(6);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(FeedbackQuestion::class)->orderBy('sort_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class);
    }

    public function isOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->closes_at && $this->closes_at->isPast()) {
            return false;
        }

        return true;
    }
}
