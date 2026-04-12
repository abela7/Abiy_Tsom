<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FasikaQuizSubmission extends Model
{
    protected $fillable = [
        'participant_name',
        'ip_address',
        'user_agent',
        'score',
        'total_possible',
        'answers',
        'time_taken_seconds',
    ];

    protected function casts(): array
    {
        return [
            'answers'            => 'array',
            'score'              => 'integer',
            'total_possible'     => 'integer',
            'time_taken_seconds' => 'integer',
        ];
    }

    public function percentageScore(): int
    {
        if ($this->total_possible === 0) {
            return 0;
        }

        return (int) round(($this->score / $this->total_possible) * 100);
    }

    public function correctCount(): int
    {
        return count(array_filter((array) $this->answers, fn ($a) => $a['is_correct'] ?? false));
    }

    public function formattedTime(): string
    {
        if ($this->time_taken_seconds === null) {
            return '—';
        }

        $m = intdiv($this->time_taken_seconds, 60);
        $s = $this->time_taken_seconds % 60;

        return $m . ':' . str_pad((string) $s, 2, '0', STR_PAD_LEFT);
    }
}
