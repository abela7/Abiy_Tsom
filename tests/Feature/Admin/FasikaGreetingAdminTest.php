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

    public function test_admin_can_update_fasika_greeting_sender_name(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $share = FasikaGreetingShare::query()->create([
            'share_token' => 'fasika-share-edit-abc',
            'sender_name' => 'Old Name',
            'sender_name_normalized' => 'old name',
            'open_count' => 0,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.fasika-greetings.update', $share),
            ['sender_name' => '  New Display Name  ']
        );

        $response->assertRedirect(route('admin.fasika-greetings.index'))
            ->assertSessionHas('success', __('app.fasika_greeting_update_success', ['name' => 'New Display Name']));

        $share->refresh();
        $this->assertSame('New Display Name', $share->sender_name);
        $this->assertSame('new display name', $share->sender_name_normalized);
    }

    public function test_admin_update_fasika_greeting_validation_errors_preserve_row_input(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $share = FasikaGreetingShare::query()->create([
            'share_token' => 'fasika-share-edit-xyz',
            'sender_name' => 'Valid',
            'sender_name_normalized' => 'valid',
        ]);

        $response = $this->actingAs($admin)->from(route('admin.fasika-greetings.index'))->patch(
            route('admin.fasika-greetings.update', $share),
            ['sender_name' => '   ']
        );

        $response->assertRedirect(route('admin.fasika-greetings.index'))
            ->assertSessionHasErrors('sender_name')
            ->assertSessionHas('fasika_greeting_failed_token', 'fasika-share-edit-xyz');

        $share->refresh();
        $this->assertSame('Valid', $share->sender_name);
    }

    public function test_admin_can_delete_single_fasika_greeting_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $share = FasikaGreetingShare::query()->create([
            'share_token' => 'fasika-share-delete-123',
            'sender_name' => 'Abel',
            'sender_name_normalized' => 'abel',
            'open_count' => 2,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.fasika-greetings.destroy', $share));

        $response->assertRedirect(route('admin.fasika-greetings.index'))
            ->assertSessionHas('success', __('app.fasika_greeting_delete_success', ['name' => 'Abel']));

        $this->assertDatabaseMissing('fasika_greeting_shares', [
            'id' => $share->id,
        ]);
    }

    public function test_admin_can_clear_all_fasika_greeting_records(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        FasikaGreetingShare::query()->create([
            'share_token' => 'fasika-share-clear-123',
            'sender_name' => 'Abel',
            'sender_name_normalized' => 'abel',
        ]);

        FasikaGreetingShare::query()->create([
            'share_token' => 'fasika-share-clear-456',
            'sender_name' => 'Sara',
            'sender_name_normalized' => 'sara',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.fasika-greetings.clear-all'));

        $response->assertRedirect(route('admin.fasika-greetings.index'))
            ->assertSessionHas('success', __('app.fasika_greeting_clear_all_success', ['count' => 2]));

        $this->assertDatabaseCount('fasika_greeting_shares', 0);
    }
}
