<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('himamat_days', function (Blueprint $table): void {
            $table->longText('spiritual_meaning_en')->nullable()->after('title_am');
            $table->longText('spiritual_meaning_am')->nullable()->after('spiritual_meaning_en');
            $table->text('ritual_guide_intro_en')->nullable()->after('spiritual_meaning_am');
            $table->text('ritual_guide_intro_am')->nullable()->after('ritual_guide_intro_en');
        });
    }

    public function down(): void
    {
        Schema::table('himamat_days', function (Blueprint $table): void {
            $table->dropColumn([
                'spiritual_meaning_en',
                'spiritual_meaning_am',
                'ritual_guide_intro_en',
                'ritual_guide_intro_am',
            ]);
        });
    }
};
