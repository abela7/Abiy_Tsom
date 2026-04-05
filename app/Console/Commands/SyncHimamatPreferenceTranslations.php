<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;

class SyncHimamatPreferenceTranslations extends Command
{
    /**
     * @var list<string>
     */
    private const KEYS = [
        'himamat_eyebrow',
        'himamat_preferences_title',
        'himamat_preferences_intro',
        'himamat_preferences_master_title',
        'himamat_preferences_master_body',
        'himamat_preferences_saved',
        'himamat_save_button',
        'himamat_slot_7am',
        'himamat_slot_9am',
        'himamat_slot_12pm',
        'himamat_slot_3pm',
        'himamat_slot_5pm',
        'himamat_preferences_timeline_hint',
        'himamat_timezone_label',
        'himamat_timezone_value',
        'himamat_day_view_title',
        'himamat_open_today',
        'himamat_slots_label',
    ];

    protected $signature = 'himamat:sync-preferences-translations
        {--dry-run : Preview the Himamat preference translations without saving}';

    protected $description = 'Overwrite the Himamat preferences translation keys in the database with the current file values';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        foreach (['en', 'am'] as $locale) {
            $strings = $this->loadLocaleStrings($locale);

            foreach (self::KEYS as $key) {
                $value = trim((string) ($strings[$key] ?? ''));

                if ($value === '') {
                    $this->warn(sprintf('Skipping missing [%s] key for locale [%s].', $key, $locale));

                    continue;
                }

                if ($dryRun) {
                    $this->line(sprintf('[dry-run] %s %s = %s', $locale, $key, $value));
                    $updated++;

                    continue;
                }

                Translation::updateOrCreate(
                    [
                        'group' => 'himamat',
                        'key' => $key,
                        'locale' => $locale,
                    ],
                    ['value' => $value]
                );

                $updated++;
            }
        }

        if (! $dryRun) {
            Translation::clearCache();
        }

        $this->info(sprintf(
            '%s %d Himamat preference translation value(s).',
            $dryRun ? 'Previewed' : 'Updated',
            $updated
        ));

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function loadLocaleStrings(string $locale): array
    {
        $path = lang_path($locale.'/app.php');

        if (! is_file($path)) {
            return [];
        }

        $strings = require $path;

        return is_array($strings) ? $strings : [];
    }
}
