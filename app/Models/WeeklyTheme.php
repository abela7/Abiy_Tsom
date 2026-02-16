<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One of the 8 weekly themes (Zewerede, Kidist, etc.).
 */
class WeeklyTheme extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'lent_season_id',
        'week_number',
        'name_geez',
        'name_en',
        'name_am',
        'meaning',
        'description',
        'gospel_reference',
        'epistles_reference',
        'psalm_reference',
        'liturgy',
        'theme_summary',
        'week_start_date',
        'week_end_date',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'week_end_date' => 'date',
        ];
    }

    /**
     * The lent season this theme belongs to.
     */
    public function lentSeason(): BelongsTo
    {
        return $this->belongsTo(LentSeason::class);
    }

    /**
     * Daily content entries under this week.
     */
    public function dailyContents(): HasMany
    {
        return $this->hasMany(DailyContent::class);
    }
}
