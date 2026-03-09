<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores sanitized admin audit events for dashboard write actions.
 */
class AuditLog extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'admin_user_id',
        'route_name',
        'action',
        'method',
        'url',
        'target_type',
        'target_id',
        'target_label',
        'status_code',
        'ip_address',
        'user_agent',
        'request_summary',
        'changed_fields',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_summary' => 'array',
            'changed_fields' => 'array',
            'meta' => 'array',
        ];
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
