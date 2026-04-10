<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberFeedback extends Model
{
    protected $table = 'member_feedbacks';

    public const USEFULNESS_OPTIONS = [
        'very_useful',
        'useful',
        'not_very_useful',
        'not_useful',
        'not_seen',
    ];

    public const CONTINUITY_OPTIONS = [
        'all_seasons',
        'abiy_tsom_only',
    ];

    protected $fillable = [
        'member_id',
        'token',
        'status',
        'q1_usefulness',
        'q2_improvement_feedback',
        'q3_continuity_preference',
        'q4_overall_rating',
        'submitted_at',
        'last_saved_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'q4_overall_rating' => 'integer',
            'submitted_at'      => 'datetime',
            'last_saved_at'     => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Calculates which Alpine wizard step this member should resume on.
     *
     * Step 1: Q1 usefulness
     * Step 2: Q2 improvement feedback  (negative branch: not_very_useful / not_useful)
     * Step 3: Q3 continuity preference (positive branch: very_useful / useful)
     * Step 4: Q4 overall rating        (both branches merge here)
     */
    public function calculateCurrentStep(): int
    {
        if ($this->status === 'submitted') {
            return 4;
        }

        if ($this->q1_usefulness === null) {
            return 1;
        }

        // Positive branch (very_useful / useful) → Q3 → Q4
        if (in_array($this->q1_usefulness, ['very_useful', 'useful'], true)) {
            return $this->q3_continuity_preference !== null ? 4 : 3;
        }

        // Negative branch (not_very_useful / not_useful) → Q2 → Q4
        if (in_array($this->q1_usefulness, ['not_very_useful', 'not_useful'], true)) {
            return $this->q2_improvement_feedback !== null ? 4 : 2;
        }

        // not_seen should have early-exited, but handle defensively
        return 1;
    }

    public function surveyUrl(): string
    {
        return route('survey.show', ['token' => $this->token]);
    }
}
