<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A saint image for the Sinksar section of a day.
 */
class DailyContentSinksarImage extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'daily_content_id',
        'image_path',
        'caption_en',
        'caption_am',
        'sort_order',
    ];

    public function dailyContent(): BelongsTo
    {
        return $this->belongsTo(DailyContent::class);
    }

    /**
     * Full public URL for the image.
     */
    public function imageUrl(): string
    {
        return url(Storage::disk('public')->url($this->image_path));
    }
}
