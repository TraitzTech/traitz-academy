<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePayment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'reference',
        'receipt_number',
        'mesomb_transaction_id',
        'payer_phone',
        'provider',
        'amount',
        'base_amount',
        'surcharge_amount',
        'surcharge_percentage',
        'currency',
        'payment_type',
        'installment_number',
        'total_installments',
        'status',
        'failure_reason',
        'raw_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'surcharge_amount' => 'decimal:2',
            'surcharge_percentage' => 'decimal:2',
            'installment_number' => 'integer',
            'total_installments' => 'integer',
            'raw_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
