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
        'description_en',
        'description_am',
        'sort_order',
    ];

    public function dailyContent(): BelongsTo
    {
        return $this->belongsTo(DailyContent::class);
    }
}
