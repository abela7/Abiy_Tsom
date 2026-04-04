<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberHimamatPreference extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'member_id',
        'lent_season_id',
        'enabled',
        'intro_enabled',
        'third_enabled',
        'sixth_enabled',
        'ninth_enabled',
        'eleventh_enabled',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'intro_enabled' => 'boolean',
            'third_enabled' => 'boolean',
            'sixth_enabled' => 'boolean',
            'ninth_enabled' => 'boolean',
            'eleventh_enabled' => 'boolean',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function lentSeason(): BelongsTo
    {
        return $this->belongsTo(LentSeason::class);
    }

    /**
     * @return array<string, bool>
     */
    public static function defaultValues(): array
    {
        return [
            'enabled' => true,
            'intro_enabled' => true,
            'third_enabled' => true,
            'sixth_enabled' => true,
            'ninth_enabled' => true,
            'eleventh_enabled' => true,
        ];
    }

    public function slotEnabled(string $slotKey): bool
    {
        $column = $slotKey.'_enabled';

        return $this->enabled && (bool) ($this->{$column} ?? false);
    }
}
