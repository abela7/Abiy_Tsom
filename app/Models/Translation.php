<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-managed translations stored in the database.
 * Used for the translation management page.
 */
class Translation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'group',
        'key',
        'locale',
        'value',
    ];

    /**
     * Get all translations for a given locale, grouped by group.
     *
     * @return array<string, array<string, string>>
     */
    public static function getForLocale(string $locale): array
    {
        return Cache::remember(
            "translations.{$locale}",
            now()->addHour(),
            function () use ($locale): array {
                return self::where('locale', $locale)
                    ->get()
                    ->groupBy('group')
                    ->map(fn ($items) => $items->pluck('value', 'key')->toArray())
                    ->toArray();
            }
        );
    }

    /**
     * Clear the translation cache for all locales.
     */
    public static function clearCache(): void
    {
        Cache::forget('translations.en');
        Cache::forget('translations.am');
    }

    /**
     * Load translations from DB only — single source of truth.
     * Admin edits in DB; user and admin see the same value.
     */
    public static function loadFromDb(string $locale): void
    {
        if (! in_array($locale, ['en', 'am'], true)) {
            return;
        }

        try {
            $dbLines = [];
            foreach (self::getForLocale($locale) as $groupLines) {
                foreach ($groupLines as $key => $value) {
                    if ((string) $value !== '') {
                        $dbLines[$key] = (string) $value;
                    }
                }
            }

            $fileLines = [];
            $filePath = base_path("lang/{$locale}/app.php");
            if (is_file($filePath)) {
                $fileData = require $filePath;
                if (is_array($fileData)) {
                    $fileLines = $fileData;
                }
            }

            // Keep current setup: file is base, DB overrides.
            $lines = array_merge($fileLines, $dbLines);

            if (empty($lines)) {
                return;
            }

            $translator = app('translator');
            $ref = new \ReflectionClass($translator);
            $prop = $ref->getProperty('loaded');
            $prop->setAccessible(true);
            $loaded = $prop->getValue($translator) ?: [];
            $loaded['*'] = $loaded['*'] ?? [];
            $loaded['*']['app'] = $loaded['*']['app'] ?? [];
            $loaded['*']['app'][$locale] = $lines;
            $prop->setValue($translator, $loaded);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
