<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Amharic UI was updated in lang files (ነጸብራቅ → ዕለታዊ መልዕክት), but member-facing text can still
 * come from (1) the translations table, which overrides files, and (2) activity names in DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        $reflectionAmStrings = [
            'reflection' => 'ዕለታዊ መልዕክት',
            'telegram_nav_reflection' => 'ዕለታዊ መልዕክት',
            'step_reflection_refs' => 'ዕለታዊ መልዕክት እና ማጣቀሻዎች',
            'reflection_label' => 'ዕለታዊ መልዕክት',
            'daily_message' => 'ዕለታዊ መልዕክት',
        ];

        if (Schema::hasTable('translations')) {
            foreach ($reflectionAmStrings as $key => $value) {
                DB::table('translations')
                    ->where('locale', 'am')
                    ->where('key', $key)
                    ->update([
                        'value' => $value,
                        'updated_at' => now(),
                    ]);
            }
        }

        $activityReplacements = [
            'ዕለታዊ ነጸብራቅ / መልእክት' => 'ዕለታዊ መልዕክት',
            'ዕለታዊ ነጸብራቅ' => 'ዕለታዊ መልዕክት',
            'ነጸብራቅ' => 'ዕለታዊ መልዕክት',
        ];

        if (Schema::hasTable('activities')) {
            foreach (['name_am', 'name'] as $column) {
                if (! Schema::hasColumn('activities', $column)) {
                    continue;
                }
                foreach ($activityReplacements as $from => $to) {
                    DB::table('activities')->where($column, $from)->update([$column => $to]);
                }
            }
        }

        if (Schema::hasTable('member_custom_activities') && Schema::hasColumn('member_custom_activities', 'name')) {
            foreach ($activityReplacements as $from => $to) {
                DB::table('member_custom_activities')->where('name', $from)->update(['name' => $to]);
            }
        }

        Cache::forget('translations.en');
        Cache::forget('translations.am');
    }

    public function down(): void
    {
        // Intentionally empty: wording change is not reverted.
    }
};
