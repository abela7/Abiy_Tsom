<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentSuggestion extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'language',
        'status',
        'submitter_name',
        'title',
        'reference',
        'author',
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
}
