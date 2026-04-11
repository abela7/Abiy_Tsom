<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FasikaGreetingShare extends Model
{
    protected $fillable = [
        'share_token',
        'sender_name',
        'sender_name_normalized',
        'open_count',
        'creator_ip',
        'creator_user_agent',
        'first_opened_at',
        'last_opened_at',
        'last_opened_ip',
        'last_opened_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'first_opened_at' => 'datetime',
            'last_opened_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'share_token';
    }

    public function recordOpen(Request $request): void
    {
        $openedAt = now();

        $this->forceFill([
            'open_count' => $this->open_count + 1,
            'first_opened_at' => $this->first_opened_at ?? $openedAt,
            'last_opened_at' => $openedAt,
            'last_opened_ip' => $request->ip(),
            'last_opened_user_agent' => (string) $request->userAgent(),
        ])->save();
    }
}
