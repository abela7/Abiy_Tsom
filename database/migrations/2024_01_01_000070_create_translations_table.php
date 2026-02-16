<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed translations.
     * Stores key-value pairs for each locale.
     * The admin translation page writes here;
     * the app reads from here to show Amharic.
     */
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->comment('Logical group: general, activities, ui, etc.');
            $table->string('key')->comment('Translation key, e.g. welcome_message');
            $table->string('locale', 5)->comment('en, am');
            $table->text('value');
            $table->timestamps();

            $table->unique(['group', 'key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
