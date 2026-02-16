<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Member-defined custom activity for their personal checklist.
 * Always scoped by member_id — each member has their own activities.
 */
class MemberCustomActivity extends Model
{
    protected static function booted(): void
    {
        static::saving(function (MemberCustomActivity $model): void {
            if (empty($model->member_id)) {
                throw new \InvalidArgumentException('member_id is required for MemberCustomActivity');
            }
        });
    }
    protected $table = 'member_custom_activities';

    /** @var list<string> */
    protected $fillable = [
        'member_id',
        'name',
        'sort_order',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function customChecklists(): HasMany
    {
        return $this->hasMany(MemberCustomChecklist::class);
    }
}
