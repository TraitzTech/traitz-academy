<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstalmentPayment extends Model
{
    /** @use HasFactory<\Database\Factories\InstalmentPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'course_instalment_plan_id',
        'user_id',
        'instalment_number',
        'amount',
        'status',
        'transaction_reference',
        'due_date',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'instalment_number' => 'integer',
            'amount' => 'decimal:2',
            'due_date' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' ||
            ($this->status === 'pending' && $this->due_date->isPast());
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(fn ($q) => $q->where('status', 'pending')->where('due_date', '<', now()));
    }
}
