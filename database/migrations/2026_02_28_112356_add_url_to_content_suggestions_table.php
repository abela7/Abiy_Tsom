<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_suggestions', function (Blueprint $table) {
            $table->string('url', 500)->nullable()->after('author');
        });
    }

    public function down(): void
    {
        Schema::table('content_suggestions', function (Blueprint $table) {
            $table->dropColumn('url');
        });
    }
};
