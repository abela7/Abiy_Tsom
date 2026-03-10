<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ContentSuggestion extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'type',
        'content_area',
        'language',
        'ethiopian_month',
        'ethiopian_day',
        'entry_scope',
        'status',
        'submitter_name',
        'title',
        'reference',
        'author',
        'url',
        'image_path',
        'structured_payload',
        'content_detail',
        'notes',
        'ip_address',
        'used_by_id',
        'used_at',
        'admin_notes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'structured_payload' => 'array',
        ];
    }

    /**
     * The admin user who submitted this suggestion (nullable for anonymous).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who marked this suggestion as used.
     */
    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_id');
    }

    /**
     * Human-readable label for content type.
     */
    public function typeLabel(): string
    {
        if (filled($this->content_area)) {
            return match ($this->content_area) {
                'lectionary' => __('app.telegram_suggest_area_lectionary'),
                'bible_reading' => __('app.telegram_suggest_area_bible_reading'),
                'synaxarium' => __('app.telegram_suggest_area_sinksar'),
                'synaxarium_celebration' => __('app.telegram_suggest_area_synaxarium_celebration'),
                'daily_message' => __('app.telegram_suggest_area_daily_message'),
                'mezmur' => __('app.telegram_suggest_area_mezmur'),
                'spiritual_book' => __('app.telegram_suggest_area_spiritual_book'),
                'reference_resource' => __('app.telegram_suggest_area_reference_resource'),
                default => ucfirst((string) $this->content_area),
            };
        }

        return match ($this->type) {
            'bible'     => __('app.suggest_type_bible'),
            'mezmur'    => __('app.suggest_type_mezmur'),
            'sinksar'   => __('app.suggest_type_sinksar'),
            'book'      => __('app.suggest_type_book'),
            'reference' => __('app.suggest_type_reference'),
            default     => ucfirst($this->type),
        };
    }

    /**
     * Display name: user's name if linked, else submitter_name, else 'Anonymous'.
     */
    public function displayName(): string
    {
        return $this->user?->name
            ?? $this->submitter_name
            ?? __('app.suggest_anonymous');
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function ethiopianDateLabel(): ?string
    {
        if ($this->content_area === 'synaxarium_celebration' && $this->entry_scope === 'monthly' && $this->ethiopian_day) {
            return __('app.synaxarium_day_number_short', ['day' => $this->ethiopian_day]);
        }

        if (! $this->ethiopian_month || ! $this->ethiopian_day) {
            return null;
        }

        $monthName = match ((int) $this->ethiopian_month) {
            1 => 'Meskerem',
            2 => 'Tikimt',
            3 => 'Hidar',
            4 => 'Tahsas',
            5 => 'Tir',
            6 => 'Yekatit',
            7 => 'Megabit',
            8 => 'Miyazia',
            9 => 'Ginbot',
            10 => 'Sene',
            11 => 'Hamle',
            12 => 'Nehase',
            13 => 'Pagumen',
            default => null,
        };

        if ($monthName === null) {
            return null;
        }

        return $monthName.' '.$this->ethiopian_day;
    }

    public function entryScopeLabel(): ?string
    {
        return match ($this->entry_scope) {
            'yearly' => __('app.telegram_suggest_scope_yearly'),
            'monthly' => __('app.telegram_suggest_scope_monthly'),
            default => null,
        };
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return url(Storage::disk('public')->url($this->image_path));
    }

    public function structuredValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->structured_payload ?? [], $key, $default);
    }
}
