<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralClick extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'visitor_hash',
        'ip_address',
        'user_agent',
        'referer',
        'is_unique',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
