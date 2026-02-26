<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberFundraisingResponse extends Model
{
    protected $fillable = [
        'member_id',
        'campaign_id',
        'status',
        'last_snoozed_date',
        'contact_name',
        'contact_phone',
        'interested_at',
    ];

    protected $casts = [
        'last_snoozed_date' => 'date',
        'interested_at'     => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FundraisingCampaign::class, 'campaign_id');
    }

    /**
     * Whether the popup should be shown to this member today.
     *
     * - 'interested' → never show again.
     * - 'snoozed' + last_snoozed_date is today → user clicked
     *   "Not Today", hide until tomorrow.
     * - null (seen but no action) → keep showing until user acts.
     */
    public function shouldShowPopup(): bool
    {
        if ($this->status === 'interested') {
            return false;
        }

        if ($this->status === 'snoozed' && $this->last_snoozed_date && $this->last_snoozed_date->isToday()) {
            return false;
        }

        return true;
    }
}
