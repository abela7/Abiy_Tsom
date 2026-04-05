<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HimamatDay extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'lent_season_id',
        'slug',
        'sort_order',
        'date',
        'title_en',
        'title_am',
        'spiritual_meaning_en',
        'spiritual_meaning_am',
        'ritual_guide_intro_en',
        'ritual_guide_intro_am',
        'synaxarium_source',
        'synaxarium_month',
        'synaxarium_day',
        'is_published',
        'created_by_id',
        'updated_by_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'synaxarium_month' => 'integer',
            'synaxarium_day' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function lentSeason(): BelongsTo
    {
        return $this->belongsTo(LentSeason::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(HimamatSlot::class)->orderBy('slot_order');
    }

    public function publishedSlots(): HasMany
    {
        return $this->hasMany(HimamatSlot::class)
            ->where('is_published', true)
            ->orderBy('slot_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(HimamatDayFaq::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function memberPath(?string $slotKey = null): string
    {
        return '/member/himamat/'.$this->slug.($slotKey ? '/'.$slotKey : '');
    }

    public function accessPath(Member $member, ?string $slotKey = null): string
    {
        $path = '/himamat/access/'.$member->token.'/'.$this->slug;

        return $slotKey ? $path.'/'.$slotKey : $path;
    }
}
