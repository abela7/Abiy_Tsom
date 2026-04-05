<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lectionary extends Model
{
    protected $table = 'lectionary';

    /** @var list<string> */
    protected $fillable = [
        'month',
        'day',
        // Title & Description
        'title_am',
        'title_en',
        'description_am',
        'description_en',
        // Pauline
        'pauline_book_am',
        'pauline_book_en',
        'pauline_chapter',
        'pauline_verses',
        'pauline_text_am',
        'pauline_text_en',
        // Catholic
        'catholic_book_am',
        'catholic_book_en',
        'catholic_chapter',
        'catholic_verses',
        'catholic_text_am',
        'catholic_text_en',
        // Acts
        'acts_chapter',
        'acts_verses',
        'acts_text_am',
        'acts_text_en',
        // Mesbak
        'mesbak_psalm',
        'mesbak_verses',
        'mesbak_geez_1', 'mesbak_geez_2', 'mesbak_geez_3',
        'mesbak_text_am',
        'mesbak_text_en',
        // Gospel
        'gospel_book_am',
        'gospel_book_en',
        'gospel_chapter',
        'gospel_verses',
        'gospel_text_am',
        'gospel_text_en',
        // Qiddase
        'qiddase_am',
        'qiddase_en',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'day' => 'integer',
            'pauline_chapter' => 'integer',
            'catholic_chapter' => 'integer',
            'acts_chapter' => 'integer',
            'mesbak_psalm' => 'integer',
            'gospel_chapter' => 'integer',
        ];
    }

    /**
     * Whether this entry has at least one reading filled in.
     */
    public function hasContent(): bool
    {
        return filled($this->pauline_book_am)
            || filled($this->pauline_book_en)
            || filled($this->catholic_book_am)
            || filled($this->catholic_book_en)
            || filled($this->acts_chapter)
            || filled($this->mesbak_psalm)
            || filled($this->gospel_book_am)
            || filled($this->gospel_book_en)
            || filled($this->qiddase_am)
            || filled($this->qiddase_en);
    }
}
