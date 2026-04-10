<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberFeedback extends Model
{
    protected $table = 'member_feedbacks';

    protected $fillable = [
        'member_id',
        'token',
        'status',
        'q1_overall_rating',
        'q2_most_used_feature',
        'q3_himamat_rating',
        'q4_whatsapp_reminder_useful',
        'q5_suggestion',
        'q6_opt_in_future_fasts',
        'submitted_at',
        'last_saved_at',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'q1_overall_rating'          => 'integer',
            'q3_himamat_rating'          => 'integer',
            'q4_whatsapp_reminder_useful' => 'boolean',
            'q6_opt_in_future_fasts'     => 'boolean',
            'submitted_at'               => 'datetime',
            'last_saved_at'              => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Returns the step number the member should resume on.
     * Used to hydrate the Alpine wizard: x-data="{ step: {{ $feedback->calculateCurrentStep() }} }"
     */
    public function calculateCurrentStep(): int
    {
        if ($this->status === 'submitted') {
            return 5;
        }

        if ($this->q5_suggestion !== null) {
            return 5;
        }

        if ($this->q3_himamat_rating !== null || $this->q4_whatsapp_reminder_useful !== null) {
            return 4;
        }

        if ($this->q2_most_used_feature !== null) {
            return 3;
        }

        if ($this->q1_overall_rating !== null) {
            return 2;
        }

        return 1;
    }

    /**
     * The survey URL that is sent to the member via WhatsApp.
     */
    public function surveyUrl(): string
    {
        return route('survey.show', ['token' => $this->token]);
    }
}
