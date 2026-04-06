<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\Member;
use App\Services\HimamatWhatsAppTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HimamatWhatsAppTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_slot_reminder_prefers_reminder_header_over_hour_title_and_prefers_whatsapp_language(): void
    {
        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'whatsapp_language' => 'am',
            'theme' => 'light',
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => 1,
            'slug' => 'holy-monday',
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'title_am' => 'AM Holy Monday',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'third',
            'slot_order' => 2,
            'scheduled_time_london' => '09:00:00',
            'slot_header_en' => 'EN Hour Title',
            'slot_header_am' => 'AM Hour Title',
            'reminder_header_en' => 'EN Reminder Header',
            'reminder_header_am' => 'AM Reminder Header',
            'reminder_content_en' => 'English reminder content.',
            'reminder_content_am' => 'Amharic reminder content.',
            'is_published' => true,
        ]);

        $rendered = app(HimamatWhatsAppTemplateService::class)->renderReminder(
            $member,
            $day,
            $slot,
            'https://example.com/day#himamat-slot-third'
        );

        $this->assertSame('am', $rendered['locale']);
        $this->assertStringContainsString('AM Reminder Header', $rendered['message']);
        $this->assertStringContainsString('Amharic reminder content.', $rendered['message']);
        $this->assertStringNotContainsString('EN Hour Title', $rendered['message']);
        $this->assertStringNotContainsString('English reminder content.', $rendered['message']);
    }

    public function test_slot_reminder_defaults_to_amharic_when_member_has_no_valid_language(): void
    {
        $member = Member::create([
            'baptism_name' => 'Dawit',
            'token' => str_repeat('b', 64),
            'locale' => 'fr',
            'whatsapp_language' => null,
            'theme' => 'light',
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => 1,
            'slug' => 'holy-monday',
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'title_am' => 'AM Holy Monday',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'sixth',
            'slot_order' => 3,
            'scheduled_time_london' => '12:00:00',
            'slot_header_en' => 'EN Noon Hour Title',
            'slot_header_am' => 'AM Noon Hour Title',
            'reminder_header_en' => 'EN Noon Reminder Header',
            'reminder_header_am' => 'AM Noon Reminder Header',
            'reminder_content_en' => 'English noon reminder content.',
            'reminder_content_am' => 'Amharic noon reminder content.',
            'is_published' => true,
        ]);

        $rendered = app(HimamatWhatsAppTemplateService::class)->renderReminder(
            $member,
            $day,
            $slot,
            'https://example.com/day#himamat-slot-sixth'
        );

        $this->assertSame('am', $rendered['locale']);
        $this->assertStringContainsString('AM Noon Reminder Header', $rendered['message']);
        $this->assertStringContainsString('Amharic noon reminder content.', $rendered['message']);
        $this->assertStringNotContainsString('English noon reminder content.', $rendered['message']);
    }

    public function test_slot_reminder_falls_back_to_hour_title_when_reminder_header_is_blank(): void
    {
        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('c', 64),
            'locale' => 'am',
            'whatsapp_language' => 'am',
            'theme' => 'light',
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => 1,
            'slug' => 'holy-tuesday',
            'sort_order' => 2,
            'date' => '2026-04-07',
            'title_en' => 'Holy Tuesday',
            'title_am' => 'AM Holy Tuesday',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'third',
            'slot_order' => 2,
            'scheduled_time_london' => '09:00:00',
            'slot_header_en' => 'EN Tuesday Hour Title',
            'slot_header_am' => 'AM Tuesday Hour Title',
            'reminder_header_en' => '',
            'reminder_header_am' => '',
            'reminder_content_en' => 'English reminder content.',
            'reminder_content_am' => 'Amharic reminder content.',
            'is_published' => true,
        ]);

        $rendered = app(HimamatWhatsAppTemplateService::class)->renderReminder(
            $member,
            $day,
            $slot,
            'https://example.com/day#himamat-slot-third'
        );

        $this->assertStringContainsString('AM Tuesday Hour Title', $rendered['message']);
        $this->assertStringContainsString('Amharic reminder content.', $rendered['message']);
    }
}
