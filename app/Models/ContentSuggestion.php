<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentSuggestion extends Model
{
    protected $fillable = [
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
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type'     => 'string',
        'language' => 'string',
        'status'   => 'string',
    ];

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
}
