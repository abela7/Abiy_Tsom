<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HimamatSeasonFaq extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'lent_season_id',
        'sort_order',
        'question_en',
        'question_am',
        'answer_en',
        'answer_am',
        'created_by_id',
        'updated_by_id',
    ];

    public function lentSeason(): BelongsTo
    {
        return $this->belongsTo(LentSeason::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
