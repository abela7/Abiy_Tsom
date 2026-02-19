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
        'name_en',
        'name_am',
        'description',
        'description_en',
        'description_am',
        'sort_order',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getNameEnAttribute(?string $value): ?string
    {
        return $this->fallbackLocalizedValue($value, $this->attributes['name'] ?? null);
    }

    public function getNameAmAttribute(?string $value): ?string
    {
        return $this->fallbackLocalizedValue($value, $this->attributes['name'] ?? null);
    }

    public function getDescriptionEnAttribute(?string $value): ?string
    {
        return $this->fallbackLocalizedValue($value, $this->attributes['description'] ?? null);
    }

    public function getDescriptionAmAttribute(?string $value): ?string
    {
        return $this->fallbackLocalizedValue($value, $this->attributes['description'] ?? null);
    }

    protected function fallbackLocalizedValue(?string $localizedValue, ?string $fallbackValue): ?string
    {
        $value = trim((string) $localizedValue);
        if ($value !== '') {
            return $localizedValue;
        }

        $fallback = trim((string) $fallbackValue);
        return $fallback !== '' ? $fallbackValue : null;
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

    /**
     * Admin user who created this activity.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Admin user who last updated this activity.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
