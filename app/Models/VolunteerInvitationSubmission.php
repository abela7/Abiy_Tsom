<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerInvitationSubmission extends Model
{
    public const DECISION_INTERESTED = 'interested';
    public const DECISION_NO_TIME = 'no_time';
    public const DECISION_NOT_INTERESTED = 'not_interested';

    public const CONTACT_METHOD_WHATSAPP = 'whatsapp';
    public const CONTACT_METHOD_PHONE = 'phone';
    public const CONTACT_METHOD_TELEGRAM = 'telegram';

    /** @var list<string> */
    protected $fillable = [
        'volunteer_invitation_campaign_id',
        'visitor_token',
        'ip_address',
        'user_agent',
        'referer',
        'opened_at',
        'video_started_at',
        'video_completed_at',
        'video_skipped_at',
        'decision',
        'decision_at',
        'contact_name',
        'phone',
        'preferred_contact_method',
        'contact_submitted_at',
        'shared_at',
        'last_activity_at',
        'open_count',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'opened_at'             => 'datetime',
            'video_started_at'       => 'datetime',
            'video_completed_at'     => 'datetime',
            'video_skipped_at'       => 'datetime',
            'decision_at'            => 'datetime',
            'contact_submitted_at'   => 'datetime',
            'shared_at'              => 'datetime',
            'last_activity_at'       => 'datetime',
            'open_count'             => 'integer',
            'created_at'             => 'datetime',
            'updated_at'             => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VolunteerInvitationCampaign::class, 'volunteer_invitation_campaign_id');
    }

    public function isInterested(): bool
    {
        return $this->decision === self::DECISION_INTERESTED;
    }

    public function isRefused(): bool
    {
        return in_array($this->decision, [self::DECISION_NO_TIME, self::DECISION_NOT_INTERESTED], true);
    }

    public function hasContact(): bool
    {
        return $this->contact_submitted_at !== null;
    }
}
