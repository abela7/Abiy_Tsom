<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberHimamatInvitationDelivery;
use App\Models\MemberHimamatPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HimamatTrackingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_himamat_tracking_page_with_click_and_preference_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $clickedMember = Member::create([
            'baptism_name' => 'Abel Teklu',
            'token' => str_repeat('a', 64),
            'locale' => 'am',
            'theme' => 'sepia',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        $notClickedMember = Member::create([
            'baptism_name' => 'Bethlehem Desta',
            'token' => str_repeat('b', 64),
            'locale' => 'en',
            'theme' => 'sepia',
            'whatsapp_phone' => '+447700900222',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        MemberHimamatInvitationDelivery::create([
            'member_id' => $clickedMember->id,
            'campaign_key' => 'holy-monday-launch',
            'channel' => 'whatsapp',
            'destination_phone' => $clickedMember->whatsapp_phone,
            'status' => 'sent',
            'open_count' => 2,
            'delivered_at' => now()->subHour(),
            'first_opened_at' => now()->subMinutes(50),
            'last_opened_at' => now()->subMinutes(10),
        ]);

        MemberHimamatInvitationDelivery::create([
            'member_id' => $notClickedMember->id,
            'campaign_key' => 'holy-monday-launch',
            'channel' => 'whatsapp',
            'destination_phone' => $notClickedMember->whatsapp_phone,
            'status' => 'sent',
            'open_count' => 0,
            'delivered_at' => now()->subMinutes(30),
        ]);

        MemberHimamatPreference::create([
            'member_id' => $clickedMember->id,
            'lent_season_id' => $season->id,
            'enabled' => true,
            'intro_enabled' => true,
            'third_enabled' => false,
            'sixth_enabled' => true,
            'ninth_enabled' => false,
            'eleventh_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.himamat.tracking', ['campaign' => 'holy-monday-launch']))
            ->assertOk()
            ->assertSee('Invitation Tracking')
            ->assertSee('Abel Teklu')
            ->assertSee('Bethlehem Desta')
            ->assertSee('2')
            ->assertSee('7 oclock - (7:00am)')
            ->assertSee('3 oclock - (9:00am)')
            ->assertSee('Not clicked');
    }
}
