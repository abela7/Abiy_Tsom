<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerInvitationCampaign extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'seo_title',
        'seo_description',
        'youtube_url',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(VolunteerInvitationSubmission::class, 'volunteer_invitation_campaign_id');
    }
}
