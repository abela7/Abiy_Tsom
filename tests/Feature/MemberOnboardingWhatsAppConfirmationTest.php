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
        $response = $this->postJson('/register', [
            'baptism_name' => 'Martha',
            'phone' => '+447123456789',
            'locale' => 'en',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'verification_pending' => true,
            ]);

        $this->assertDatabaseHas('members', [
            'baptism_name' => 'Martha',
            'whatsapp_phone' => '+447123456789',
            'whatsapp_reminder_enabled' => 0,
            'whatsapp_confirmation_status' => 'none',
        ]);
    }
}
