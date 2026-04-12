<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FasikaQuizQuestion extends Model
{
    protected $fillable = [
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'difficulty',
        'points',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'points'    => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'id'         => $this->id,
            'question'   => $this->question,
            'option_a'   => $this->option_a,
            'option_b'   => $this->option_b,
            'option_c'   => $this->option_c,
            'option_d'   => $this->option_d,
            'difficulty' => $this->difficulty,
            'points'     => $this->points,
        ];
    }

    public function difficultyLabel(): string
    {
        return match ($this->difficulty) {
            'easy'   => 'ቀላል',
            'medium' => 'መካከለኛ',
            'hard'   => 'ከባድ',
            default  => $this->difficulty,
        };
    }
}
