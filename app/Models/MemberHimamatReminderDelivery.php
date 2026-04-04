<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberHimamatReminderDelivery extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'member_id',
        'himamat_slot_id',
        'channel',
        'due_at_london',
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
            'due_at_london' => 'datetime',
            'last_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function himamatSlot(): BelongsTo
    {
        return $this->belongsTo(HimamatSlot::class);
    }
}
