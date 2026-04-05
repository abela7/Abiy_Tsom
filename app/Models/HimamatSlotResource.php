<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class HimamatSlotResource extends Model
{
    public const TYPE_VIDEO = 'video';

    public const TYPE_WEBSITE = 'website';

    public const TYPE_PDF = 'pdf';

    public const TYPE_PHOTO = 'photo';

    /** @var list<string> */
    protected $fillable = [
        'himamat_slot_id',
        'type',
        'sort_order',
        'title_en',
        'title_am',
        'url',
        'file_path',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [
            self::TYPE_VIDEO,
            self::TYPE_WEBSITE,
            self::TYPE_PDF,
            self::TYPE_PHOTO,
        ];
    }

    public function himamatSlot(): BelongsTo
    {
        return $this->belongsTo(HimamatSlot::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function resolvedUrl(): ?string
    {
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        return $this->url ?: null;
    }

    public function isPhoto(): bool
    {
        return $this->type === self::TYPE_PHOTO;
    }
}
