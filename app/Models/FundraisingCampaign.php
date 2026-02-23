<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundraisingCampaign extends Model
{
    protected $fillable = [
        'title',
        'title_am',
        'description',
        'description_am',
        'youtube_url',
        'donate_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(MemberFundraisingResponse::class, 'campaign_id');
    }

    /**
     * Returns the single active campaign, or null if none.
     */
    public static function active(): ?self
    {
        return static::where('is_active', true)->latest()->first();
    }

    /**
     * Returns the title in the requested locale, falling back to English.
     */
    public function localizedTitle(string $locale = 'en'): string
    {
        if ($locale === 'am' && ! empty($this->title_am)) {
            return $this->title_am;
        }

        return $this->title ?? '';
    }

    /**
     * Returns the description in the requested locale, falling back to English.
     */
    public function localizedDescription(string $locale = 'en'): ?string
    {
        if ($locale === 'am' && ! empty($this->description_am)) {
            return $this->description_am;
        }

        return $this->description;
    }

    /**
     * Extract a YouTube embed URL from any YouTube URL format.
     * Returns null if the URL is not a recognisable YouTube link.
     */
    public function youtubeEmbedUrl(): ?string
    {
        if (! $this->youtube_url) {
            return null;
        }

        $url = $this->youtube_url;

        // youtu.be/VIDEO_ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1].'?rel=0';
        }

        // youtube.com/watch?v=VIDEO_ID  or  youtube.com/embed/VIDEO_ID
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/))([a-zA-Z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1].'?rel=0';
        }

        return null;
    }
}
