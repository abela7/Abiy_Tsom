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

    public function test_slot_reminder_prefers_whatsapp_language_over_member_locale(): void
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
            'title_am' => 'ሰኞ',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'third',
            'slot_order' => 2,
            'scheduled_time_london' => '09:00:00',
            'slot_header_en' => 'Monday morning 3 oclock Gospel reading',
            'slot_header_am' => 'ሰኞ ጠዋት 3 የሚነበበው የዕለቱ ወንጌል',
            'reminder_header_en' => 'Third Hour Header',
            'reminder_header_am' => 'የ3 ሰዓት ርዕስ',
            'reminder_content_en' => 'English reminder content.',
            'reminder_content_am' => 'አማርኛ የማሳሰቢያ ይዘት።',
            'is_published' => true,
        ]);

        $rendered = app(HimamatWhatsAppTemplateService::class)->renderReminder(
            $member,
            $day,
            $slot,
            'https://example.com/day#himamat-slot-third'
        );

        $this->assertSame('am', $rendered['locale']);
        $this->assertStringContainsString('ሰኞ ጠዋት 3 የሚነበበው የዕለቱ ወንጌል', $rendered['message']);
        $this->assertStringContainsString('አማርኛ የማሳሰቢያ ይዘት።', $rendered['message']);
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
            'title_am' => 'ሰኞ',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'sixth',
            'slot_order' => 3,
            'scheduled_time_london' => '12:00:00',
            'slot_header_en' => 'Monday 6 oclock Gospel reading',
            'slot_header_am' => 'ሰኞ ቀትር 6 ሰዓት የሚነበበው የዕለቱ ወንጌል',
            'reminder_header_en' => 'Sixth Hour Header',
            'reminder_header_am' => 'የ6 ሰዓት ርዕስ',
            'reminder_content_en' => 'English noon reminder content.',
            'reminder_content_am' => 'አማርኛ የቀትር ማሳሰቢያ ይዘት።',
            'is_published' => true,
        ]);

        $rendered = app(HimamatWhatsAppTemplateService::class)->renderReminder(
            $member,
            $day,
            $slot,
            'https://example.com/day#himamat-slot-sixth'
        );

        $this->assertSame('am', $rendered['locale']);
        $this->assertStringContainsString('ሰኞ ቀትር 6 ሰዓት የሚነበበው የዕለቱ ወንጌል', $rendered['message']);
        $this->assertStringContainsString('አማርኛ የቀትር ማሳሰቢያ ይዘት።', $rendered['message']);
        $this->assertStringNotContainsString('English noon reminder content.', $rendered['message']);
    }
}
