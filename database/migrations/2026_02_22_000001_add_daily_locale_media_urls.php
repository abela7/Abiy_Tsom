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
        if (! Schema::hasColumn('daily_contents', 'sinksar_url_en')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->string('sinksar_url_en', 500)->nullable()->after('sinksar_url');
            });
        }

        if (! Schema::hasColumn('daily_contents', 'sinksar_url_am')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->string('sinksar_url_am', 500)->nullable()->after('sinksar_url_en');
            });
        }

        if (! Schema::hasColumn('daily_content_mezmurs', 'url_en')) {
            Schema::table('daily_content_mezmurs', function (Blueprint $table): void {
                $table->string('url_en', 500)->nullable()->after('url');
            });
        }

        if (! Schema::hasColumn('daily_content_mezmurs', 'url_am')) {
            Schema::table('daily_content_mezmurs', function (Blueprint $table): void {
                $table->string('url_am', 500)->nullable()->after('url_en');
            });
        }

        if (! Schema::hasColumn('daily_content_books', 'url_en')) {
            Schema::table('daily_content_books', function (Blueprint $table): void {
                $table->string('url_en', 500)->nullable()->after('url');
            });
        }

        if (! Schema::hasColumn('daily_content_books', 'url_am')) {
            Schema::table('daily_content_books', function (Blueprint $table): void {
                $table->string('url_am', 500)->nullable()->after('url_en');
            });
        }

        if (! Schema::hasColumn('daily_content_references', 'url_en')) {
            Schema::table('daily_content_references', function (Blueprint $table): void {
                $table->string('url_en', 500)->nullable()->after('url');
            });
        }

        if (! Schema::hasColumn('daily_content_references', 'url_am')) {
            Schema::table('daily_content_references', function (Blueprint $table): void {
                $table->string('url_am', 500)->nullable()->after('url_en');
            });
        }

        if (Schema::hasTable('daily_contents') && Schema::hasColumn('daily_contents', 'sinksar_url')) {
            DB::table('daily_contents')->update([
                'sinksar_url_en' => DB::raw('COALESCE(sinksar_url_en, sinksar_url)'),
                'sinksar_url_am' => DB::raw('COALESCE(sinksar_url_am, sinksar_url)'),
            ]);
        }

        if (Schema::hasColumn('daily_content_mezmurs', 'url')) {
            DB::table('daily_content_mezmurs')->update([
                'url_en' => DB::raw('COALESCE(url_en, url)'),
                'url_am' => DB::raw('COALESCE(url_am, url)'),
            ]);
        }

        if (Schema::hasColumn('daily_content_books', 'url')) {
            DB::table('daily_content_books')->update([
                'url_en' => DB::raw('COALESCE(url_en, url)'),
                'url_am' => DB::raw('COALESCE(url_am, url)'),
            ]);
        }

        if (Schema::hasColumn('daily_content_references', 'url')) {
            DB::table('daily_content_references')->update([
                'url_en' => DB::raw('COALESCE(url_en, url)'),
                'url_am' => DB::raw('COALESCE(url_am, url)'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_contents', 'sinksar_url_en')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->dropColumn('sinksar_url_en');
            });
        }

        if (Schema::hasColumn('daily_contents', 'sinksar_url_am')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->dropColumn('sinksar_url_am');
            });
        }

        if (Schema::hasColumn('daily_content_mezmurs', 'url_en')) {
            Schema::table('daily_content_mezmurs', function (Blueprint $table): void {
                $table->dropColumn('url_en');
            });
        }

        if (Schema::hasColumn('daily_content_mezmurs', 'url_am')) {
            Schema::table('daily_content_mezmurs', function (Blueprint $table): void {
                $table->dropColumn('url_am');
            });
        }

        if (Schema::hasColumn('daily_content_books', 'url_en')) {
            Schema::table('daily_content_books', function (Blueprint $table): void {
                $table->dropColumn('url_en');
            });
        }

        if (Schema::hasColumn('daily_content_books', 'url_am')) {
            Schema::table('daily_content_books', function (Blueprint $table): void {
                $table->dropColumn('url_am');
            });
        }

        if (Schema::hasColumn('daily_content_references', 'url_en')) {
            Schema::table('daily_content_references', function (Blueprint $table): void {
                $table->dropColumn('url_en');
            });
        }

        if (Schema::hasColumn('daily_content_references', 'url_am')) {
            Schema::table('daily_content_references', function (Blueprint $table): void {
                $table->dropColumn('url_am');
            });
        }
    }
};
