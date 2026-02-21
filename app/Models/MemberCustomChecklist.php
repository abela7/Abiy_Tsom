<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Completion tracking for a member's custom activity on a given day.
 * Always scoped by member_id — never mix data between members.
 */
class MemberCustomChecklist extends Model
{
    protected static function booted(): void
    {
        static::saving(function (MemberCustomChecklist $model): void {
            if (empty($model->member_id)) {
                throw new \InvalidArgumentException('member_id is required for MemberCustomChecklist');
            }
        });
    }

    protected $table = 'member_custom_checklists';

    /** @var list<string> */
    protected $fillable = [
        'member_id',
        'daily_content_id',
        'member_custom_activity_id',
        'completed',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
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

    public function customActivity(): BelongsTo
    {
        return $this->belongsTo(MemberCustomActivity::class, 'member_custom_activity_id');
    }
}
