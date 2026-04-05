<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncHimamatPreferenceTranslationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_overwrites_existing_himamat_preference_translation_values(): void
    {
        Translation::create([
            'group' => 'himamat',
            'key' => 'himamat_slot_9am',
            'locale' => 'am',
            'value' => '3 ሰዓት - ሰዓተ ሣልስት (9:00 AM)',
        ]);

        $this->artisan('himamat:sync-preferences-translations')
            ->expectsOutput('Updated 36 Himamat preference translation value(s).')
            ->assertSuccessful();

        $this->assertDatabaseHas('translations', [
            'group' => 'himamat',
            'key' => 'himamat_slot_9am',
            'locale' => 'am',
            'value' => '3 ሰዓት - (9:00am)',
        ]);
    }
}
