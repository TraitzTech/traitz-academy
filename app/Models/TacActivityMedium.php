<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacActivityMedium extends Model
{
    use HasFactory;

    protected $table = 'tac_activity_media';

    protected $fillable = [
        'tac_activity_id',
        'path',
        'caption',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(TacActivity::class, 'tac_activity_id');
    }
}
