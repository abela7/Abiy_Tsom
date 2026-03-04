<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            if (! Schema::hasColumn('daily_contents', 'sinksar_text_en')) {
                $table->text('sinksar_text_en')->nullable()->after('sinksar_description_en');
            }

            if (! Schema::hasColumn('daily_contents', 'sinksar_text_am')) {
                $table->text('sinksar_text_am')->nullable()->after('sinksar_text_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $columns = [];
            if (Schema::hasColumn('daily_contents', 'sinksar_text_en')) {
                $columns[] = 'sinksar_text_en';
            }
            if (Schema::hasColumn('daily_contents', 'sinksar_text_am')) {
                $columns[] = 'sinksar_text_am';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
