<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiForgeRegistration extends Model
{
    /** @use HasFactory<\Database\Factories\AiForgeRegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'ai_forge_event_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'organization',
        'motivation',
        'status',
        'amount_paid',
        'payment_status',
        'payment_reference',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
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
}
