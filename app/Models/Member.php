<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Church member — identified by a localStorage token.
 */
class Member extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'baptism_name',
        'token',
        'referred_by',
        'telegram_chat_id',
        'trusted_device_hash',
        'passcode',
        'passcode_enabled',
        'locale',
        'theme',
        'tour_completed_at',
        'whatsapp_reminder_enabled',
        'whatsapp_phone',
        'whatsapp_reminder_time',
        'whatsapp_last_sent_date',
        'whatsapp_language',
        'whatsapp_confirmation_status',
        'whatsapp_confirmation_requested_at',
        'whatsapp_confirmation_responded_at',
        'whatsapp_non_uk_requested',
    ];

    /** @var list<string> */
    protected $hidden = [
        'passcode',
        'token',
        'trusted_device_hash',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'passcode_enabled' => 'boolean',
            'whatsapp_reminder_enabled' => 'boolean',
            'whatsapp_last_sent_date' => 'date',
            'whatsapp_confirmation_requested_at' => 'datetime',
            'whatsapp_confirmation_responded_at' => 'datetime',
            'tour_completed_at' => 'datetime',
            'whatsapp_non_uk_requested' => 'boolean',
        ];
    }

    /**
     * All checklist entries for this member.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(MemberChecklist::class);
    }

    /**
     * Member-defined custom activities.
     */
    public function customActivities(): HasMany
    {
        return $this->hasMany(MemberCustomActivity::class);
    }

    /**
     * Custom activity checklist entries.
     */
    public function customChecklists(): HasMany
    {
        return $this->hasMany(MemberCustomChecklist::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MemberSession::class);
    }

    public function reminderLinkOpens(): HasMany
    {
        return $this->hasMany(MemberReminderOpen::class);
    }

    public function dailyViews(): HasMany
    {
        return $this->hasMany(MemberDailyView::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Members who are confirmed and ready to receive WhatsApp messages.
     */
    public function scopeActiveConfirmedWhatsApp(Builder $query): Builder
    {
        return $query
            ->where('whatsapp_reminder_enabled', true)
            ->where('whatsapp_confirmation_status', 'confirmed')
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '');
    }
}
