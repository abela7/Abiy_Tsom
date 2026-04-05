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
            $table->string('synaxarium_title_en')->nullable()->after('ritual_guide_intro_am');
            $table->string('synaxarium_title_am')->nullable()->after('synaxarium_title_en');
            $table->longText('synaxarium_text_en')->nullable()->after('synaxarium_title_am');
            $table->longText('synaxarium_text_am')->nullable()->after('synaxarium_text_en');
        });
    }

    public function down(): void
    {
        Schema::table('himamat_days', function (Blueprint $table): void {
            $table->dropColumn([
                'synaxarium_title_en',
                'synaxarium_title_am',
                'synaxarium_text_en',
                'synaxarium_text_am',
            ]);
        });
    }
};
