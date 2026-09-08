<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TacCompetitionCriterion extends Model
{
    use HasFactory;

    protected $table = 'tac_competition_criteria';

    protected $fillable = [
        'tac_activity_id',
        'label',
        'description',
        'max_score',
        'weight',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'integer',
            'weight' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(TacActivity::class, 'tac_activity_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(TacCompetitionScore::class);
    }
}
