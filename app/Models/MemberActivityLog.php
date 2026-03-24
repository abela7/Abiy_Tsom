<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class MemberActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'url',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Log a member activity from a request context.
     */
    public static function log(
        Member $member,
        string $action,
        ?string $description = null,
        ?Request $request = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'member_id' => $member->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 512) : null,
            'url' => $request ? mb_substr($request->fullUrl(), 0, 512) : null,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
