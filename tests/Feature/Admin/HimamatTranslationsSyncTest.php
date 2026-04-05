<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Database\Seeders\TranslationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HimamatTranslationsSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_himamat_translation_keys_are_seeded_into_the_himamat_group(): void
    {
        (new TranslationSeeder)->run();

        $this->assertDatabaseHas('translations', [
            'group' => 'himamat',
            'locale' => 'en',
            'key' => 'himamat_preferences_title',
            'value' => 'Holy Week Reminders',
        ]);

        $this->assertDatabaseHas('translations', [
            'group' => 'himamat',
            'locale' => 'am',
            'key' => 'himamat_slot_9am',
            'value' => '3 ሰዓት - (9:00am)',
        ]);

        $this->assertDatabaseMissing('translations', [
            'group' => 'himamat',
            'locale' => 'en',
            'key' => 'himamat_admin_subtitle',
        ]);
    }
}
