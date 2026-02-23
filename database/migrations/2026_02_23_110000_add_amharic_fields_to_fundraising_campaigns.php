<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fundraising_campaigns', function (Blueprint $table) {
            $table->string('title_am')->nullable()->after('title');
            $table->text('description_am')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('fundraising_campaigns', function (Blueprint $table) {
            $table->dropColumn(['title_am', 'description_am']);
        });
    }
};
