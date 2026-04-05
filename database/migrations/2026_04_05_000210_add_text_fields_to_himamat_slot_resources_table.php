<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('himamat_slot_resources', function (Blueprint $table): void {
            $table->longText('text_en')->nullable()->after('title_am');
            $table->longText('text_am')->nullable()->after('text_en');
        });
    }

    public function down(): void
    {
        Schema::table('himamat_slot_resources', function (Blueprint $table): void {
            $table->dropColumn([
                'text_en',
                'text_am',
            ]);
        });
    }
};
