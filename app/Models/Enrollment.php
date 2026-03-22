<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'instalment_plan_id',
        'payment_type',
        'access_status',
        'progress',
        'enrolled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress'     => 'integer',
            'enrolled_at'  => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->access_status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->access_status === 'suspended';
    }

    public function isCompleted(): bool
    {
        return $this->access_status === 'completed';
    }

    public function scopeActive($query)
    {
        return $query->where('access_status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('access_status', 'completed');
    }

    public function scopeSuspended($query)
    {
        return $query->where('access_status', 'suspended');
    }
}
