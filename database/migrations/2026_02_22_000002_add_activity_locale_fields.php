<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_am')->nullable()->after('name_en');
            $table->text('description_en')->nullable()->after('description');
            $table->text('description_am')->nullable()->after('description_en');
        });

        foreach (DB::table('activities')->get(['id', 'name', 'description']) as $activity) {
            DB::table('activities')
                ->where('id', $activity->id)
                ->update([
                    'name_en' => $activity->name,
                    'name_am' => $activity->name,
                    'description_en' => $activity->description,
                    'description_am' => $activity->description,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropColumn(['name_en', 'name_am', 'description_en', 'description_am']);
        });
    }
};
