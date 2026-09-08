<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacCompetitionScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'tac_competition_entry_id',
        'tac_competition_criterion_id',
        'judge_id',
        'score',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(TacCompetitionEntry::class, 'tac_competition_entry_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(TacCompetitionCriterion::class, 'tac_competition_criterion_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }
}
