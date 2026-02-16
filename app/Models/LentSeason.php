<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single year's lent season (e.g. 2026: Feb 16 - Apr 12).
 */
class LentSeason extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'year',
        'start_date',
        'end_date',
        'total_days',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Weekly themes for this season.
     */
    public function weeklyThemes(): HasMany
    {
        return $this->hasMany(WeeklyTheme::class);
    }

    /**
     * Daily content entries for this season.
     */
    public function dailyContents(): HasMany
    {
        return $this->hasMany(DailyContent::class);
    }

    /**
     * Activities defined for this season.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the currently active season.
     */
    public static function active(): ?self
    {
        return self::where('is_active', true)->first();
    }
}
