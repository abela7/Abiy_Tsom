<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\FasikaGreetingShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FasikaGreetingAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_fasika_greeting_dashboard_with_stats(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        FasikaGreetingShare::query()->create([
            'share_token' => 'fasika-share-one-123',
            'sender_name' => 'Abel',
            'sender_name_normalized' => 'abel',
            'open_count' => 3,
            'first_opened_at' => now()->subHour(),
            'last_opened_at' => now()->subMinutes(10),
        ]);

        FasikaGreetingShare::query()->create([
            'share_token' => 'fasika-share-two-456',
            'sender_name' => 'Sara',
            'sender_name_normalized' => 'sara',
            'open_count' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.fasika-greetings.index'));

        $response->assertOk()
            ->assertSee(__('app.fasika_greeting_admin_title'))
            ->assertSee('Abel')
            ->assertSee('Sara')
            ->assertSee((string) 2)
            ->assertSee((string) 1)
            ->assertSee((string) 3)
            ->assertSee(route('public.yefasika-beal'));
    }
}
