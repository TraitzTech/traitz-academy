<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseInstalmentPlan extends Model
{
    /** @use HasFactory<\Database\Factories\CourseInstalmentPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'number_of_instalments',
        'amount_per_instalment',
        'interval_in_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'number_of_instalments' => 'integer',
            'amount_per_instalment' => 'decimal:2',
            'interval_in_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->number_of_instalments * $this->amount_per_instalment;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
