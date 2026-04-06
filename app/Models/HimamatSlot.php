<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HimamatSlot extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'himamat_day_id',
        'slot_key',
        'slot_order',
        'scheduled_time_london',
        'slot_header_en',
        'slot_header_am',
        'reminder_header_en',
        'reminder_header_am',
        'reminder_content_en',
        'reminder_content_am',
        'spiritual_significance_en',
        'spiritual_significance_am',
        'reading_reference_en',
        'reading_reference_am',
        'reading_text_en',
        'reading_text_am',
        'prostration_count',
        'prostration_guidance_en',
        'prostration_guidance_am',
        'short_prayer_en',
        'short_prayer_am',
        'is_published',
        'created_by_id',
        'updated_by_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function himamatDay(): BelongsTo
    {
        return $this->belongsTo(HimamatDay::class);
    }

    public function reminderDeliveries(): HasMany
    {
        return $this->hasMany(MemberHimamatReminderDelivery::class);
    }

    public function reminderDispatches(): HasMany
    {
        return $this->hasMany(HimamatReminderDispatch::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(HimamatSlotResource::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
