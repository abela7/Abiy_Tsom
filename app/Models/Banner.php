<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'title',
        'title_am',
        'description',
        'description_am',
        'image',
        'image_en',
        'button_label',
        'button_label_am',
        'button_url',
        'is_active',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function localizedTitle(?string $locale = null): ?string
    {
        return $this->localizedField($locale, $this->title, $this->title_am);
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        return $this->localizedField($locale, $this->description, $this->description_am);
    }

    public function localizedButtonLabel(?string $locale = null): string
    {
        return $this->localizedField($locale, $this->button_label, $this->button_label_am)
            ?? __('app.banner_interested');
    }

    public function imageUrl(?string $locale = null): ?string
    {
        $path = $this->imageForLocale($locale);

        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function imageForLocale(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'en' && is_string($this->image_en) && trim($this->image_en) !== '') {
            return $this->image_en;
        }

        return $this->image;
    }

    /**
     * Return locale-aware text: EN field for 'en', AM field for 'am', with fallback.
     */
    private function localizedField(?string $locale, ?string $enValue, ?string $amValue): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'am' && is_string($amValue) && trim($amValue) !== '') {
            return $amValue;
        }

        if (is_string($enValue) && trim($enValue) !== '') {
            return $enValue;
        }

        return null;
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(BannerResponse::class);
    }
}
