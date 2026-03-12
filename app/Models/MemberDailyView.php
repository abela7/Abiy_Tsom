<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks unique views per daily content page.
 * - Member views: one row per member per day (member_id set)
 * - Anonymous views: one row per IP per day (member_id null)
 */
class MemberDailyView extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'member_id',
        'daily_content_id',
        'ip_address',
        'viewed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function dailyContent(): BelongsTo
    {
        return $this->belongsTo(DailyContent::class);
    }

    /**
     * Check if this is an anonymous (non-member) view.
     */
    public function isAnonymous(): bool
    {
        return $this->member_id === null;
    }
}
