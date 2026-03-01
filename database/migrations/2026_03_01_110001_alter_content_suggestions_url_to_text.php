<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('content_suggestions', 'url')) {
            return;
        }

        Schema::table('content_suggestions', function (Blueprint $table): void {
            $table->text('url')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('content_suggestions', 'url')) {
            return;
        }

        Schema::table('content_suggestions', function (Blueprint $table): void {
            $table->string('url', 500)->nullable()->change();
        });
    }
};
