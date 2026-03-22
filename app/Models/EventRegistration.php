<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    /** @use HasFactory<\Database\Factories\EventRegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'status',
        'attended_at',
    ];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
