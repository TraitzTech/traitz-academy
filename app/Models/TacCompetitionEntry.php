<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TacCompetitionEntry extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_SCORED = 'scored';

    public const STATUS_DISQUALIFIED = 'disqualified';

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_UNDER_REVIEW => 'Under review',
        self::STATUS_SCORED => 'Scored',
        self::STATUS_DISQUALIFIED => 'Disqualified',
    ];

    protected $fillable = [
        'tac_activity_id',
        'community_member_id',
        'title',
        'description',
        'repo_url',
        'demo_url',
        'video_url',
        'attachment_path',
        'team_name',
        'team_members',
        'status',
        'submitted_at',
        'total_score',
        'rank',
        'is_winner',
        'award',
        'judge_notes',
        'results_published_at',
    ];

    protected function casts(): array
    {
        return [
            'team_members' => 'array',
            'submitted_at' => 'datetime',
            'results_published_at' => 'datetime',
            'total_score' => 'decimal:2',
            'rank' => 'integer',
            'is_winner' => 'boolean',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(TacActivity::class, 'tac_activity_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(CommunityMember::class, 'community_member_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(TacCompetitionScore::class);
    }

    public function scopePublishedResults(Builder $query): Builder
    {
        return $query->whereNotNull('results_published_at');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    /**
     * Weighted average across judges: each judge's weighted total is computed
     * against the rubric, then averaged so a differing number of judges per
     * entry does not skew the leaderboard.
     */
    public function recalculateScore(): void
    {
        $criteria = $this->activity?->competitionCriteria ?? collect();

        if ($criteria->isEmpty()) {
            $this->forceFill(['total_score' => null])->save();

            return;
        }

        $weightTotal = (int) $criteria->sum('weight');

        if ($weightTotal <= 0) {
            $this->forceFill(['total_score' => null])->save();

            return;
        }

        $perJudge = $this->scores()->get()->groupBy('judge_id');

        if ($perJudge->isEmpty()) {
            $this->forceFill(['total_score' => null])->save();

            return;
        }

        $judgeTotals = $perJudge->map(function ($scores) use ($criteria, $weightTotal) {
            $weighted = 0.0;

            foreach ($scores as $score) {
                $criterion = $criteria->firstWhere('id', $score->tac_competition_criterion_id);

                if (! $criterion || $criterion->max_score <= 0) {
                    continue;
                }

                // Normalise to a 0-100 scale so criteria with different maxima
                // combine fairly, then apply the criterion weight.
                $weighted += ($score->score / $criterion->max_score) * 100 * $criterion->weight;
            }

            return $weighted / $weightTotal;
        });

        $this->forceFill([
            'total_score' => round($judgeTotals->avg(), 2),
            'status' => self::STATUS_SCORED,
        ])->save();
    }
}
