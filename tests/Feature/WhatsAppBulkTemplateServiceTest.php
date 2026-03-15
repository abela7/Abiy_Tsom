<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Translation;
use App\Services\WhatsAppTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppBulkTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_message_exposes_explicit_english_and_amharic_sections(): void
    {
        $this->storeBulkTranslations();

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
            'Important update',
            'Please read this today.',
            'https://example.com/bulk'
        );

        $this->assertSame('EN HEADER: Important update', $rendered['variables']['header_en']);
        $this->assertSame('EN CONTENT: Please read this today.', $rendered['variables']['content_en']);
        $this->assertSame('AM HEADER: Important update', $rendered['variables']['header_am']);
        $this->assertSame('AM CONTENT: Please read this today.', $rendered['variables']['content_am']);
        $this->assertSame('EN HEADER: Important update', $rendered['header']);
        $this->assertSame('EN CONTENT: Please read this today.', $rendered['content']);
        $this->assertStringContainsString('EN HEADER: Important update', $rendered['message']);
        $this->assertStringContainsString('EN CONTENT: Please read this today.', $rendered['message']);
    }

    public function test_bulk_message_generic_placeholders_follow_the_members_locale(): void
    {
        $this->storeBulkTranslations();

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
            'Important update',
            'Please read this today.',
            'https://example.com/bulk'
        );

        $this->assertSame('AM HEADER: Important update', $rendered['header']);
        $this->assertSame('AM CONTENT: Please read this today.', $rendered['content']);
        $this->assertStringContainsString('AM HEADER: Important update', $rendered['message']);
        $this->assertStringContainsString('AM CONTENT: Please read this today.', $rendered['message']);
        $this->assertStringNotContainsString('EN HEADER: Important update', $rendered['message']);
    }

    private function storeBulkTranslations(): void
    {
        $translations = [
            ['group' => 'whatsapp_member', 'key' => 'whatsapp_bulk_message_header', 'locale' => 'en', 'value' => 'EN HEADER: :header'],
            ['group' => 'whatsapp_member', 'key' => 'whatsapp_bulk_message_content', 'locale' => 'en', 'value' => 'EN CONTENT: :content'],
            ['group' => 'whatsapp_member', 'key' => 'whatsapp_bulk_message_final', 'locale' => 'en', 'value' => "Hello :name\n\n:header_en\n\n:content_en\n\n:url"],
            ['group' => 'whatsapp_member', 'key' => 'whatsapp_bulk_message_header', 'locale' => 'am', 'value' => 'AM HEADER: :header'],
            ['group' => 'whatsapp_member', 'key' => 'whatsapp_bulk_message_content', 'locale' => 'am', 'value' => 'AM CONTENT: :content'],
            ['group' => 'whatsapp_member', 'key' => 'whatsapp_bulk_message_final', 'locale' => 'am', 'value' => "ሰላም :name\n\n:header_am\n\n:content_am\n\n:url"],
        ];

        foreach ($translations as $translation) {
            Translation::updateOrCreate(
                [
                    'group' => $translation['group'],
                    'key' => $translation['key'],
                    'locale' => $translation['locale'],
                ],
                ['value' => $translation['value']]
            );
        }

        Translation::clearCache();
    }
}
