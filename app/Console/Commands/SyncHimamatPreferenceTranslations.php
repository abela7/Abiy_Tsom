<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;

class SyncHimamatPreferenceTranslations extends Command
{
    /**
     * @var list<array{group: string, key: string}>
     */
    private const KEYS = [
        ['group' => 'himamat', 'key' => 'himamat_eyebrow'],
        ['group' => 'himamat', 'key' => 'himamat_preferences_title'],
        ['group' => 'himamat', 'key' => 'himamat_preferences_intro'],
        ['group' => 'himamat', 'key' => 'himamat_preferences_master_title'],
        ['group' => 'himamat', 'key' => 'himamat_preferences_master_body'],
        ['group' => 'himamat', 'key' => 'himamat_preferences_saved'],
        ['group' => 'himamat', 'key' => 'himamat_save_button'],
        ['group' => 'himamat', 'key' => 'himamat_slot_7am'],
        ['group' => 'himamat', 'key' => 'himamat_slot_9am'],
        ['group' => 'himamat', 'key' => 'himamat_slot_12pm'],
        ['group' => 'himamat', 'key' => 'himamat_slot_3pm'],
        ['group' => 'himamat', 'key' => 'himamat_slot_5pm'],
        ['group' => 'himamat', 'key' => 'himamat_preferences_timeline_hint'],
        ['group' => 'himamat', 'key' => 'himamat_timezone_label'],
        ['group' => 'himamat', 'key' => 'himamat_timezone_value'],
        ['group' => 'himamat', 'key' => 'himamat_day_view_title'],
        ['group' => 'himamat', 'key' => 'himamat_open_today'],
        ['group' => 'himamat', 'key' => 'himamat_slots_label'],
        ['group' => 'himamat', 'key' => 'himamat_ritual_intro_title'],
        ['group' => 'himamat', 'key' => 'himamat_slot_reminder_open_line'],
        ['group' => 'whatsapp_member', 'key' => 'whatsapp_himamat_intro_content'],
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

            foreach (self::KEYS as $item) {
                $key = $item['key'];
                $group = $item['group'];
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
                        'group' => $group,
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
