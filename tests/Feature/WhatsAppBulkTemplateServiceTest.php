<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
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
            'ሰላም :name, ይህ የአማርኛ መልእክት ነው።'
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
            'ሰላም :name, ይህ የአማርኛ መልእክት ነው።'
        );

        $this->assertStringContainsString('ሰላም Abel', $rendered['message']);
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
            'ሰላም :name, ይህ የአማርኛ መልእክት ነው።'
        );

        $this->assertSame('am', $rendered['locale']);
        $this->assertStringContainsString('ሰላም Abel', $rendered['message']);
    }
}
