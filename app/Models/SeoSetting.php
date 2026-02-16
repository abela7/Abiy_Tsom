<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Global SEO settings managed from admin.
 */
class SeoSetting extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Return all settings as key => value.
     *
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        if (! Schema::hasTable('seo_settings')) {
            return [];
        }

        /** @var array<string, string|null> $settings */
        $settings = Cache::remember('seo_settings.all', now()->addHour(), static function (): array {
            try {
                return self::query()
                    ->pluck('value', 'key')
                    ->toArray();
            } catch (\Throwable) {
                return [];
            }
        });

        return $settings;
    }

    /**
     * Get a setting by key from cache.
     */
    public static function cached(string $key): ?string
    {
        $val = self::allCached()[$key] ?? null;

        return is_string($val) && $val !== '' ? $val : null;
    }

    /**
     * Upsert many SEO settings.
     *
     * @param  array<string, string|null>  $values
     */
    public static function upsertValues(array $values): void
    {
        if ($values === []) {
            return;
        }

        foreach ($values as $key => $value) {
            self::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        self::clearCache();
    }

    /**
     * Clear SEO settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('seo_settings.all');
    }
}

