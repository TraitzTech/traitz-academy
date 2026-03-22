<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiForgeOrder extends Model
{
    /** @use HasFactory<\Database\Factories\AiForgeOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'ai_forge_event_id',
        'user_id',
        'order_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'subtotal',
        'surcharge_amount',
        'surcharge_percentage',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'payment_provider',
        'payer_phone',
        'transaction_id',
        'receipt_number',
        'failure_reason',
        'raw_response',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'surcharge_amount' => 'decimal:2',
            'surcharge_percentage' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'raw_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AiForgeEvent::class, 'ai_forge_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AiForgeOrderItem::class);
    }
}
