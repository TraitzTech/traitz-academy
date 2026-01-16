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
        'image_url',
        'is_featured',
        'is_active',
        'capacity',
        'enrolled_count',
        'start_date',
        'end_date',
        'curriculum',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
