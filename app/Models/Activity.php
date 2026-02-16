<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-defined spiritual activity for the checklist.
 * e.g. "Pray 7 times", "Fast until 3 PM", "Give to charity".
 */
class Activity extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'lent_season_id',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * The season this activity belongs to.
     */
    public function lentSeason(): BelongsTo
    {
        return $this->belongsTo(LentSeason::class);
    }

    /**
     * Checklist entries for this activity.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(MemberChecklist::class);
    }
}
