<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Admin-posted announcement shown to members on the home page.
 */
class Announcement extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'photo',
        'title',
        'title_en',
        'description',
        'description_en',
        'youtube_url',
        'youtube_position',
        'button_label',
        'button_label_en',
        'button_url',
        'button_enabled',
        'created_by_id',
        'updated_by_id',
    ];

    /** @var array<string, string> */
    public const YOUTUBE_POSITIONS = [
        'top' => 'app.youtube_position_top',
        'end' => 'app.youtube_position_end',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'button_enabled' => 'boolean',
        ];
    }

    /**
     * Public URL for the announcement photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return Storage::disk('public')->url($this->photo);
    }

    /**
     * Whether this announcement has an action button.
     */
    public function hasButton(?string $locale = null): bool
    {
        $buttonLabel = $this->buttonLabelForLocale($locale);

        return $this->button_enabled
            && ! empty(trim((string) $buttonLabel))
            && ! empty(trim((string) $this->button_url));
    }

    /**
     * Localized title for current locale (falls back to legacy title).
     */
    public function titleForLocale(?string $locale = null): ?string
    {
        return $this->localizedField($locale, $this->title_en, $this->title);
    }

    /**
     * Localized description for current locale (falls back to legacy description).
     */
    public function descriptionForLocale(?string $locale = null): ?string
    {
        return $this->localizedField($locale, $this->description_en, $this->description);
    }

    /**
     * Localized button label for current locale (falls back to legacy label).
     */
    public function buttonLabelForLocale(?string $locale = null): ?string
    {
        return $this->localizedField($locale, $this->button_label_en, $this->button_label);
    }

    /**
     * Return locale-aware text with fallback to legacy value.
     */
    private function localizedField(?string $locale, ?string $enValue, ?string $fallbackValue): ?string
    {
        $locale = $locale ?? app()->getLocale();
        if ($locale === 'en' && is_string($enValue) && trim($enValue) !== '') {
            return $enValue;
        }

        if (is_string($fallbackValue) && trim($fallbackValue) !== '') {
            return $fallbackValue;
        }

        return null;
    }

    /**
     * Whether this announcement has a YouTube video.
     */
    public function hasYoutubeVideo(): bool
    {
        return ! empty(trim((string) $this->youtube_url))
            && $this->youtubeVideoId() !== null;
    }

    /**
     * Extract YouTube video ID from url, or null if invalid.
     */
    public function youtubeVideoId(): ?string
    {
        if (! $this->youtube_url) {
            return null;
        }
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $this->youtube_url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * YouTube position: 'top' or 'end'. Defaults to 'end' when missing.
     */
    public function getYoutubePositionAttribute($value): string
    {
        return in_array($value, ['top', 'end'], true) ? $value : 'end';
    }

    /**
     * Admin user who created this announcement.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Admin user who last updated this announcement.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
