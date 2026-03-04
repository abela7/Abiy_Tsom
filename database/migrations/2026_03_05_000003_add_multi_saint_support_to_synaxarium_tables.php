<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ethiopian_synaxarium_monthly', function (Blueprint $table): void {
            $table->dropUnique(['day']);
            $table->boolean('is_main')->default(false)->after('day');
            $table->unsignedTinyInteger('sort_order')->default(0)->after('is_main');
            $table->index('day');
        });

        DB::table('ethiopian_synaxarium_monthly')->update(['is_main' => true]);

        Schema::table('ethiopian_synaxarium_annual', function (Blueprint $table): void {
            $table->dropUnique(['month', 'day']);
            $table->boolean('is_main')->default(false)->after('day');
            $table->unsignedTinyInteger('sort_order')->default(0)->after('is_main');
            $table->text('description_en')->nullable()->after('celebration_am');
            $table->text('description_am')->nullable()->after('description_en');
            $table->index(['month', 'day']);
        });

        DB::table('ethiopian_synaxarium_annual')->update(['is_main' => true]);
    }

    public function down(): void
    {
        Schema::table('ethiopian_synaxarium_monthly', function (Blueprint $table): void {
            $table->dropIndex(['day']);
            $table->dropColumn(['is_main', 'sort_order']);
            $table->unique('day');
        });

        Schema::table('ethiopian_synaxarium_annual', function (Blueprint $table): void {
            $table->dropIndex(['month', 'day']);
            $table->dropColumn(['is_main', 'sort_order', 'description_en', 'description_am']);
            $table->unique(['month', 'day']);
        });
    }
};
