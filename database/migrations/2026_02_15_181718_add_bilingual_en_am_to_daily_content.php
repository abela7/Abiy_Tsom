<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add English + Amharic for all text fields. Amharic is default; English falls back to Amharic.
     */
    public function up(): void
    {
        // daily_contents
        $this->addBilingualColumns('daily_contents', [
            ['old' => 'day_title', 'new_en' => 'day_title_en', 'new_am' => 'day_title_am'],
            ['old' => 'bible_reference', 'new_en' => 'bible_reference_en', 'new_am' => 'bible_reference_am'],
            ['old' => 'bible_summary', 'new_en' => 'bible_summary_en', 'new_am' => 'bible_summary_am'],
            ['old' => 'sinksar_title', 'new_en' => 'sinksar_title_en', 'new_am' => 'sinksar_title_am'],
            ['old' => 'sinksar_description', 'new_en' => 'sinksar_description_en', 'new_am' => 'sinksar_description_am'],
            ['old' => 'book_title', 'new_en' => 'book_title_en', 'new_am' => 'book_title_am'],
            ['old' => 'book_description', 'new_en' => 'book_description_en', 'new_am' => 'book_description_am'],
            ['old' => 'reflection', 'new_en' => 'reflection_en', 'new_am' => 'reflection_am'],
        ]);

        // daily_content_mezmurs
        $this->addBilingualColumns('daily_content_mezmurs', [
            ['old' => 'title', 'new_en' => 'title_en', 'new_am' => 'title_am'],
            ['old' => 'description', 'new_en' => 'description_en', 'new_am' => 'description_am'],
        ]);

        // daily_content_references
        $this->addBilingualColumns('daily_content_references', [
            ['old' => 'name', 'new_en' => 'name_en', 'new_am' => 'name_am'],
        ]);
    }

    /**
     * @param  array<int, array{old: string, new_en: string, new_am: string}>  $mappings
     */
    private function addBilingualColumns(string $table, array $mappings): void
    {
        foreach ($mappings as $m) {
            if (! Schema::hasColumn($table, $m['old'])) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($m): void {
                $t->text($m['new_am'])->nullable()->after($m['old']);
                $t->text($m['new_en'])->nullable()->after($m['new_am']);
            });

            DB::table($table)->update([
                $m['new_am'] => DB::raw("`{$m['old']}`"),
            ]);

            Schema::table($table, function (Blueprint $t) use ($m): void {
                $t->dropColumn($m['old']);
            });
        }
    }

    public function down(): void
    {
        throw new \RuntimeException('Rollback of bilingual migration is not supported.');
    }
};
