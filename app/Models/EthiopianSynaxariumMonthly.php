<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EthiopianSynaxariumMonthly extends Model
{
    protected $table = 'ethiopian_synaxarium_monthly';

    /** @var list<string> */
    protected $fillable = [
        'day',
        'is_main',
        'sort_order',
        'celebration_en',
        'celebration_am',
        'image_path',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'is_main' => 'boolean',
            'sort_order' => 'integer',
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
