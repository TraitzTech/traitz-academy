<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiForgeOrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\AiForgeOrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'ai_forge_order_id',
        'ai_forge_swag_id',
        'variation',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(AiForgeOrder::class, 'ai_forge_order_id');
    }

    public function swag(): BelongsTo
    {
        return $this->belongsTo(AiForgeSwag::class, 'ai_forge_swag_id');
    }
}
