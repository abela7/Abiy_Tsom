<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberHimamatInvitationDelivery extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'member_id',
        'campaign_key',
        'channel',
        'destination_phone',
        'status',
        'attempt_count',
        'last_attempt_at',
        'delivered_at',
        'failure_reason',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
