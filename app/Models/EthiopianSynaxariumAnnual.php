<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EthiopianSynaxariumAnnual extends Model
{
    protected $table = 'ethiopian_synaxarium_annual';

    /** @var list<string> */
    protected $fillable = [
        'month',
        'day',
        'celebration_en',
        'celebration_am',
        'image_path',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'day' => 'integer',
        ];
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return url(Storage::disk('public')->url($this->image_path));
    }
}
