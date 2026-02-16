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
        'passcode',
        'passcode_enabled',
        'locale',
        'theme',
    ];

    /** @var list<string> */
    protected $hidden = [
        'passcode',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'passcode_enabled' => 'boolean',
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
}
