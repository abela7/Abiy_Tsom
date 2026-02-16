<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single checklist entry: member + day + activity = completed?
 * Always scoped by member_id — never mix data between members.
 */
class MemberChecklist extends Model
{
    protected static function booted(): void
    {
        static::saving(function (MemberChecklist $model): void {
            if (empty($model->member_id)) {
                throw new \InvalidArgumentException('member_id is required for MemberChecklist');
            }
        });
    }
    /** @var list<string> */
    protected $fillable = [
        'member_id',
        'daily_content_id',
        'activity_id',
        'completed',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
        ];
    }

    /**
     * The member who owns this check.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * The day this checklist belongs to.
     */
    public function dailyContent(): BelongsTo
    {
        return $this->belongsTo(DailyContent::class);
    }

    /**
     * The activity being tracked.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
