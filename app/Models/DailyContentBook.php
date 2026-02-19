<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A spiritual book recommendation for a day — title, URL, description.
 */
class DailyContentBook extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'daily_content_id',
        'title_en',
        'title_am',
        'url',
        'url_en',
        'url_am',
        'description_en',
        'description_am',
        'sort_order',
    ];

    public function dailyContent(): BelongsTo
    {
        return $this->belongsTo(DailyContent::class);
    }

    /**
     * Localized URL with language fallback.
     */
    public function mediaUrl(?string $locale = null): ?string
    {
        $locale = in_array($locale ?? app()->getLocale(), ['en', 'am'], true) ? ($locale ?? app()->getLocale()) : 'en';
        $enUrl = $this->url_en ?? null;
        $amUrl = $this->url_am ?? null;
        $fallbackUrl = $this->url ?? null;

        return $locale === 'en'
            ? (($enUrl !== '' ? $enUrl : null) ?: ($amUrl !== '' ? $amUrl : null) ?: ($fallbackUrl !== '' ? $fallbackUrl : null))
            : (($amUrl !== '' ? $amUrl : null) ?: ($enUrl !== '' ? $enUrl : null) ?: ($fallbackUrl !== '' ? $fallbackUrl : null));
    }
}
