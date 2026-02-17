<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberWhatsAppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_enable_whatsapp_reminder_settings(): void
    {
        $member = Member::create([
            'baptism_name' => 'Martha',
            'token' => str_repeat('m', 64),
            'locale' => 'en',
            'theme' => 'light',
        ]);

        $response = $this->postJson('/api/member/settings', [
            'whatsapp_reminder_enabled' => true,
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '08:30',
        ], [
            'X-Member-Token' => $member->token,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'whatsapp_reminder_enabled' => 1,
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '08:30:00',
        ]);
    }

    public function test_enabling_whatsapp_reminder_requires_phone_and_time(): void
    {
        $member = Member::create([
            'baptism_name' => 'Martha',
            'token' => str_repeat('n', 64),
            'locale' => 'en',
            'theme' => 'light',
        ]);

        $response = $this->postJson('/api/member/settings', [
            'whatsapp_reminder_enabled' => true,
        ], [
            'X-Member-Token' => $member->token,
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
