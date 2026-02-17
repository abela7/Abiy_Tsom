<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-curated content for a single day of the 55-day fast.
 */
class DailyContent extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'lent_season_id',
        'weekly_theme_id',
        'day_number',
        'date',
        'day_title_en',
        'day_title_am',
        'bible_reference_en',
        'bible_reference_am',
        'bible_summary_en',
        'bible_summary_am',
        'bible_text_en',
        'bible_text_am',
        'sinksar_title_en',
        'sinksar_title_am',
        'sinksar_url',
        'sinksar_description_en',
        'sinksar_description_am',
        'reflection_en',
        'reflection_am',
        'is_published',
        'created_by_id',
        'updated_by_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    /**
     * The lent season this content belongs to.
     */
    public function lentSeason(): BelongsTo
    {
        return $this->belongsTo(LentSeason::class);
    }

    /**
     * The weekly theme this day falls under.
     */
    public function weeklyTheme(): BelongsTo
    {
        return $this->belongsTo(WeeklyTheme::class);
    }

    /**
     * Checklist entries for this day.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(MemberChecklist::class);
    }

    /**
     * Mezmur entries for this day (can have multiple).
     */
    public function mezmurs(): HasMany
    {
        return $this->hasMany(DailyContentMezmur::class)->orderBy('sort_order');
    }

    /**
     * Spiritual book recommendations for this day (can have multiple).
     * Feature: Allow multiple books per day, recommend same book on different days.
     */
    public function books(): HasMany
    {
        return $this->hasMany(DailyContentBook::class)->orderBy('sort_order');
    }

    /**
     * Reference links for this day (know more about week/day).
     */
    public function references(): HasMany
    {
        return $this->hasMany(DailyContentReference::class)->orderBy('sort_order');
    }

    /**
     * Admin user who created this day entry.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Admin user who last updated this day entry.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
