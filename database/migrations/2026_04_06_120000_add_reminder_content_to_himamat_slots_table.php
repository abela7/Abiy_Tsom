<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('himamat_slots', function (Blueprint $table): void {
            $table->longText('reminder_content_en')->nullable()->after('reminder_header_am');
            $table->longText('reminder_content_am')->nullable()->after('reminder_content_en');
        });
    }

    public function down(): void
    {
        Schema::table('himamat_slots', function (Blueprint $table): void {
            $table->dropColumn([
                'reminder_content_en',
                'reminder_content_am',
            ]);
        });
    }
};
