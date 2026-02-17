<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberOnboardingWhatsAppConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_starts_whatsapp_confirmation_as_pending(): void
    {
        $response = $this->postJson('/member/register', [
            'baptism_name' => 'Martha',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_phone' => '07123456789',
            'whatsapp_reminder_time' => '08:30',
            'whatsapp_language' => 'en',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'whatsapp_confirmation_pending' => true,
            ]);

        $this->assertDatabaseHas('members', [
            'baptism_name' => 'Martha',
            'whatsapp_phone' => '+447123456789',
            'whatsapp_reminder_time' => '08:30:00',
            'whatsapp_reminder_enabled' => 0,
            'whatsapp_confirmation_status' => 'pending',
        ]);
    }
}
