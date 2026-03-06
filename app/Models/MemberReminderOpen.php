<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberReminderOpen extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'daily_content_id',
        'first_opened_at',
        'last_opened_at',
        'last_authenticated_open_at',
        'open_count',
        'authenticated_open_count',
        'public_open_count',
        'last_open_state',
        'last_ip_address',
        'last_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'first_opened_at' => 'datetime',
            'last_opened_at' => 'datetime',
            'last_authenticated_open_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function dailyContent(): BelongsTo
    {
        return $this->belongsTo(DailyContent::class);
    }
}
