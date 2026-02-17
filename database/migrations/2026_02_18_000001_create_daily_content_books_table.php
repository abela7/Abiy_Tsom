<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multiple spiritual book entries per day — migrate from single book columns.
     */
    public function up(): void
    {
        Schema::create('daily_content_books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_content_id')->constrained()->cascadeOnDelete();
            $table->string('title_en', 255)->nullable();
            $table->string('title_am', 255)->nullable();
            $table->string('url', 500)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_am')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('daily_contents') && Schema::hasColumn('daily_contents', 'book_title_en')) {
            $rows = DB::table('daily_contents')
                ->where(function ($q): void {
                    $q->whereNotNull('book_title_en')->where('book_title_en', '!=', '')
                        ->orWhereNotNull('book_title_am')->where('book_title_am', '!=', '');
                })
                ->get(['id', 'book_title_en', 'book_title_am', 'book_url', 'book_description_en', 'book_description_am']);

            foreach ($rows as $row) {
                $titleEn = trim((string) ($row->book_title_en ?? ''));
                $titleAm = trim((string) ($row->book_title_am ?? ''));
                if ($titleEn !== '' || $titleAm !== '') {
                    DB::table('daily_content_books')->insert([
                        'daily_content_id' => $row->id,
                        'title_en' => $titleEn ?: null,
                        'title_am' => $titleAm ?: null,
                        'url' => trim((string) ($row->book_url ?? '')) ?: null,
                        'description_en' => trim((string) ($row->book_description_en ?? '')) ?: null,
                        'description_am' => trim((string) ($row->book_description_am ?? '')) ?: null,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->dropColumn(['book_title_en', 'book_title_am', 'book_url', 'book_description_en', 'book_description_am']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('daily_contents') && ! Schema::hasColumn('daily_contents', 'book_title_en')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->string('book_title_en', 255)->nullable()->after('sinksar_description_en');
                $table->string('book_title_am', 255)->nullable()->after('book_title_en');
                $table->string('book_url', 500)->nullable()->after('book_title_am');
                $table->text('book_description_en')->nullable()->after('book_url');
                $table->text('book_description_am')->nullable()->after('book_description_en');
            });

            $grouped = DB::table('daily_content_books')
                ->orderBy('daily_content_id')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('daily_content_id');

            foreach ($grouped as $dailyId => $books) {
                $first = $books->first();
                DB::table('daily_contents')->where('id', $dailyId)->update([
                    'book_title_en' => $first->title_en,
                    'book_title_am' => $first->title_am,
                    'book_url' => $first->url,
                    'book_description_en' => $first->description_en,
                    'book_description_am' => $first->description_am,
                ]);
            }
        }

        Schema::dropIfExists('daily_content_books');
    }
};
