<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks unique member views per daily content page.
 */
class MemberDailyView extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'member_id',
        'daily_content_id',
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
}
