<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dayFaqs = DB::table('himamat_day_faqs')
            ->join('himamat_days', 'himamat_day_faqs.himamat_day_id', '=', 'himamat_days.id')
            ->orderBy('himamat_days.lent_season_id')
            ->orderBy('himamat_days.date')
            ->orderBy('himamat_day_faqs.sort_order')
            ->select(
                'himamat_days.lent_season_id',
                'himamat_day_faqs.question_en',
                'himamat_day_faqs.question_am',
                'himamat_day_faqs.answer_en',
                'himamat_day_faqs.answer_am',
                'himamat_day_faqs.created_by_id',
                'himamat_day_faqs.updated_by_id',
                'himamat_day_faqs.created_at',
                'himamat_day_faqs.updated_at',
            )
            ->get();

        $sortOrderBySeason = [];
        foreach ($dayFaqs as $faq) {
            $seasonId = $faq->lent_season_id;
            $sortOrderBySeason[$seasonId] = ($sortOrderBySeason[$seasonId] ?? 0) + 1;

            DB::table('himamat_season_faqs')->insert([
                'lent_season_id' => $seasonId,
                'sort_order'     => $sortOrderBySeason[$seasonId],
                'question_en'    => $faq->question_en,
                'question_am'    => $faq->question_am,
                'answer_en'      => $faq->answer_en,
                'answer_am'      => $faq->answer_am,
                'created_by_id'  => $faq->created_by_id,
                'updated_by_id'  => $faq->updated_by_id,
                'created_at'     => $faq->created_at,
                'updated_at'     => $faq->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('himamat_season_faqs')->truncate();
    }
};
