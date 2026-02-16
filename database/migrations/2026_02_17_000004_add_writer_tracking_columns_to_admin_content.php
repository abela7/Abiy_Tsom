<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('daily_contents', 'created_by_id')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->foreignId('created_by_id')
                    ->nullable()
                    ->after('is_published')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by_id')
                    ->nullable()
                    ->after('created_by_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('activities', 'created_by_id')) {
            Schema::table('activities', function (Blueprint $table): void {
                $table->foreignId('created_by_id')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by_id')
                    ->nullable()
                    ->after('created_by_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('announcements', 'created_by_id')) {
            Schema::table('announcements', function (Blueprint $table): void {
                $table->foreignId('created_by_id')
                    ->nullable()
                    ->after('button_enabled')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by_id')
                    ->nullable()
                    ->after('created_by_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_contents', 'updated_by_id')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('updated_by_id');
            });
        }
        if (Schema::hasColumn('daily_contents', 'created_by_id')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('created_by_id');
            });
        }

        if (Schema::hasColumn('activities', 'updated_by_id')) {
            Schema::table('activities', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('updated_by_id');
            });
        }
        if (Schema::hasColumn('activities', 'created_by_id')) {
            Schema::table('activities', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('created_by_id');
            });
        }

        if (Schema::hasColumn('announcements', 'updated_by_id')) {
            Schema::table('announcements', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('updated_by_id');
            });
        }
        if (Schema::hasColumn('announcements', 'created_by_id')) {
            Schema::table('announcements', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('created_by_id');
            });
        }
    }
};
