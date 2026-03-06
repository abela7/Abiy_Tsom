<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lectionary', function (Blueprint $table): void {
            // Remove per-line AM and EN fields
            $table->dropColumn([
                'mesbak_am_1', 'mesbak_am_2', 'mesbak_am_3',
                'mesbak_en_1', 'mesbak_en_2', 'mesbak_en_3',
            ]);

            // Add single combined translation fields
            $table->text('mesbak_text_am')->nullable()->after('mesbak_geez_3');
            $table->text('mesbak_text_en')->nullable()->after('mesbak_text_am');
        });
    }

    public function down(): void
    {
        Schema::table('lectionary', function (Blueprint $table): void {
            $table->dropColumn(['mesbak_text_am', 'mesbak_text_en']);
            $table->string('mesbak_am_1', 500)->nullable()->after('mesbak_geez_3');
            $table->string('mesbak_am_2', 500)->nullable()->after('mesbak_am_1');
            $table->string('mesbak_am_3', 500)->nullable()->after('mesbak_am_2');
            $table->string('mesbak_en_1', 500)->nullable()->after('mesbak_am_3');
            $table->string('mesbak_en_2', 500)->nullable()->after('mesbak_en_1');
            $table->string('mesbak_en_3', 500)->nullable()->after('mesbak_en_2');
        });
    }
};
