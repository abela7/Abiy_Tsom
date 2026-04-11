<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\WeeklyTheme;
use App\Services\WhatsAppTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppBulkTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_message_exposes_explicit_english_and_amharic_sections(): void
    {
        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'whatsapp_phone' => '+447700900111',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_language' => 'en',
        ]);

        $rendered = app(WhatsAppTemplateService::class)->renderBulkMessage(
            $member,
            'Hello :name, English bulk message.',
            'Selam :name, yih yeAmarigna melikt new.'
        );

        $this->assertSame('Abel', $rendered['variables']['name']);
        $this->assertSame('', $rendered['header']);
        $this->assertSame('', $rendered['content']);
        $this->assertSame('Hello Abel, English bulk message.', $rendered['message']);
    }

    public function test_bulk_message_generic_placeholders_follow_the_members_locale(): void
    {
        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('b', 64),
            'whatsapp_phone' => '+447700900112',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_language' => 'am',
        ]);

        $rendered = app(WhatsAppTemplateService::class)->renderBulkMessage(
            $member,
            'Hello :name, English bulk message.',
            'Selam :name, yeAmarigna melikt new.'
        );

        $this->assertStringContainsString('Selam Abel', $rendered['message']);
        $this->assertStringNotContainsString('English bulk message', $rendered['message']);
    }

    public function test_bulk_message_falls_back_to_amharic_when_member_has_no_language_preference(): void
    {
        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('c', 64),
            'whatsapp_phone' => '+447700900113',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        $rendered = app(WhatsAppTemplateService::class)->renderBulkMessage(
            $member,
            'Hello :name, English bulk message.',
            'Selam :name, yeAmarigna melikt new.'
        );

        $this->assertSame('am', $rendered['locale']);
        $this->assertStringContainsString('Selam Abel', $rendered['message']);
    }

    public function test_bulk_message_can_render_member_specific_fasika_link(): void
    {
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');
        config()->set('app.easter_date', '2026-04-12 03:00');
        config()->set('app.easter_timezone', 'Europe/London');

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 56,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Fasika',
            'meaning' => 'Resurrection',
            'week_start_date' => '2026-04-06',
            'week_end_date' => '2026-04-12',
        ]);

        $daily = DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 56,
            'date' => '2026-04-12',
            'is_published' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('z', 64),
            'whatsapp_phone' => '+447700900114',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_language' => 'am',
        ]);

        $rendered = app(WhatsAppTemplateService::class)->renderBulkMessage(
            $member,
            'Hello :name :fasika_url',
            "Selam :name\n\n:fasika_url"
        );

        $this->assertStringEndsWith(
            '/m/'.str_repeat('z', 64).'/day/56-'.$daily->id,
            $rendered['variables']['fasika_url']
        );
        $this->assertStringContainsString('/m/'.str_repeat('z', 64).'/day/56-'.$daily->id, $rendered['message']);
    }
}
