<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiForgeSwag extends Model
{
    /** @use HasFactory<\Database\Factories\AiForgeSwagFactory> */
    use HasFactory;

    protected $fillable = [
        'ai_forge_event_id',
        'name',
        'slug',
        'category',
        'description',
        'price',
        'currency',
        'image',
        'gallery_images',
        'variations',
        'stock_quantity',
        'sold_count',
        'sort_order',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'gallery_images' => 'array',
            'variations' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(AiForgeEvent::class, 'ai_forge_event_id');
    }

    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->where($field ?? 'slug', $value)->firstOrFail();
    }
}
