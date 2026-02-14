<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    /** @use HasFactory<\Database\Factories\EmailCampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'audience',
        'subject',
        'message_html',
        'action_text',
        'action_url',
        'recipient_count',
    ];

    protected function casts(): array
    {
        return [
            'recipient_count' => 'integer',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }
}
