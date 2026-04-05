<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HimamatReminderDispatch extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_COMPLETED_WITH_FAILURES = 'completed_with_failures';

    public const STATUS_MISSED = 'missed';

    /** @var list<string> */
    protected $fillable = [
        'himamat_slot_id',
        'channel',
        'due_at_london',
        'status',
        'recipient_count',
        'queued_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'dispatch_started_at',
        'dispatch_finished_at',
        'last_error',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'due_at_london' => 'datetime',
            'dispatch_started_at' => 'datetime',
            'dispatch_finished_at' => 'datetime',
        ];
    }

    public function himamatSlot(): BelongsTo
    {
        return $this->belongsTo(HimamatSlot::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(MemberHimamatReminderDelivery::class, 'himamat_reminder_dispatch_id');
    }
}
