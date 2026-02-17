<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reference link (name + URL) for "know more" about a day or week.
 */
class DailyContentReference extends Model
{
    public const TYPE_VIDEO = 'video';
    public const TYPE_WEBSITE = 'website';
    public const TYPE_FILE = 'file';

    /** @var list<string> */
    protected $fillable = ['daily_content_id', 'name_en', 'name_am', 'url', 'type', 'sort_order'];

    public function dailyContent(): BelongsTo
    {
        return $this->belongsTo(DailyContent::class);
    }
}
