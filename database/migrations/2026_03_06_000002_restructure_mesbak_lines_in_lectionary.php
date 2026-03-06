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
            // Remove old single-blob fields
            $table->dropColumn(['mesbak_text_geez', 'mesbak_text_am', 'mesbak_text_en']);

            // Add 3 structured lines (Ge'ez + Amharic + English each)
            $table->string('mesbak_geez_1', 500)->nullable()->after('mesbak_verses');
            $table->string('mesbak_geez_2', 500)->nullable()->after('mesbak_geez_1');
            $table->string('mesbak_geez_3', 500)->nullable()->after('mesbak_geez_2');

            $table->string('mesbak_am_1', 500)->nullable()->after('mesbak_geez_3');
            $table->string('mesbak_am_2', 500)->nullable()->after('mesbak_am_1');
            $table->string('mesbak_am_3', 500)->nullable()->after('mesbak_am_2');

            $table->string('mesbak_en_1', 500)->nullable()->after('mesbak_am_3');
            $table->string('mesbak_en_2', 500)->nullable()->after('mesbak_en_1');
            $table->string('mesbak_en_3', 500)->nullable()->after('mesbak_en_2');
        });
    }

    public function down(): void
    {
        Schema::table('lectionary', function (Blueprint $table): void {
            $table->dropColumn([
                'mesbak_geez_1', 'mesbak_geez_2', 'mesbak_geez_3',
                'mesbak_am_1',   'mesbak_am_2',   'mesbak_am_3',
                'mesbak_en_1',   'mesbak_en_2',   'mesbak_en_3',
            ]);
            $table->text('mesbak_text_geez')->nullable()->after('mesbak_verses');
            $table->text('mesbak_text_am')->nullable()->after('mesbak_text_geez');
            $table->text('mesbak_text_en')->nullable()->after('mesbak_text_am');
        });
    }
};
