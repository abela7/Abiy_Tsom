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
        'meaning_am',
        'description',
        'description_am',
        'gospel_reference',
        'epistles_reference',
        'epistles_reference_am',
        'epistles_text_en',
        'epistles_text_am',
        'psalm_reference',
        'liturgy',
        'theme_summary',
        'summary_am',
        'week_start_date',
        'week_end_date',
        // Structured readings content
        'feature_picture',
        'reading_1_reference',
        'reading_1_reference_am',
        'reading_1_text_en',
        'reading_1_text_am',
        'reading_2_reference',
        'reading_2_reference_am',
        'reading_2_text_en',
        'reading_2_text_am',
        'reading_3_reference',
        'reading_3_reference_am',
        'reading_3_text_en',
        'reading_3_text_am',
        'psalm_reference_am',
        'psalm_text_en',
        'psalm_text_am',
        'gospel_reference_am',
        'gospel_text_en',
        'gospel_text_am',
        'liturgy_am',
        'liturgy_text_en',
        'liturgy_text_am',
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
