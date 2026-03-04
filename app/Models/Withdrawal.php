<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const SERVICE_MTN = 'MTN';

    public const SERVICE_ORANGE = 'ORANGE';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'service',
        'receiver',
        'receiver_name',
        'currency',
        'country',
        'status',
        'mesomb_transaction_id',
        'reference',
        'failure_reason',
        'raw_response',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'raw_response' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return list<string>
     */
    public static function availableServices(): array
    {
        return [
            self::SERVICE_MTN,
            self::SERVICE_ORANGE,
        ];
    }
}
