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
            $table->string('title_am', 500)->nullable()->after('day');
            $table->string('title_en', 500)->nullable()->after('title_am');
            $table->text('description_am')->nullable()->after('title_en');
            $table->text('description_en')->nullable()->after('description_am');
        });
    }

    public function down(): void
    {
        Schema::table('lectionary', function (Blueprint $table): void {
            $table->dropColumn(['title_am', 'title_en', 'description_am', 'description_en']);
        });
    }
};
