<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'application_id',
        'user_id',
        'recorded_by',
        'updated_by',
        'program_id',
        'reference',
        'receipt_number',
        'mesomb_transaction_id',
        'payer_phone',
        'provider',
        'payment_channel',
        'amount',
        'base_amount',
        'surcharge_amount',
        'surcharge_percentage',
        'currency',
        'payment_type',
        'installment_number',
        'total_installments',
        'status',
        'manual_entry',
        'failure_reason',
        'admin_notes',
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
            'manual_entry' => 'boolean',
            'raw_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
