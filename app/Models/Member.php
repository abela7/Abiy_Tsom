<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'telegram_chat_id',
        'trusted_device_hash',
        'passcode',
        'passcode_enabled',
        'locale',
        'theme',
        'whatsapp_reminder_enabled',
        'whatsapp_phone',
        'whatsapp_reminder_time',
        'whatsapp_last_sent_date',
        'whatsapp_language',
        'whatsapp_confirmation_status',
        'whatsapp_confirmation_requested_at',
        'whatsapp_confirmation_responded_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'passcode',
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
}
