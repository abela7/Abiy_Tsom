<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\HimamatDay;
use App\Models\HimamatReminderDispatch;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HimamatReminderHealthPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_himamat_reminder_health_dashboard(): void
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

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'intro',
            'slot_order' => 1,
            'scheduled_time_london' => '07:00:00',
            'slot_header_en' => 'Daily Introduction',
            'reminder_header_en' => 'Daily Introduction',
            'is_published' => true,
        ]);

        HimamatReminderDispatch::create([
            'himamat_slot_id' => $slot->id,
            'channel' => 'whatsapp',
            'due_at_london' => '2026-04-06 07:00:00',
            'status' => HimamatReminderDispatch::STATUS_COMPLETED_WITH_FAILURES,
            'recipient_count' => 450,
            'queued_count' => 0,
            'sent_count' => 440,
            'failed_count' => 10,
            'skipped_count' => 0,
            'dispatch_started_at' => '2026-04-06 07:01:00',
            'dispatch_finished_at' => '2026-04-06 07:05:00',
            'last_error' => '10 members exhausted retries.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.himamat.reminder-health', ['date' => '2026-04-06']))
            ->assertOk()
            ->assertSee('Reminder Health')
            ->assertSee('Holy Monday')
            ->assertSee('Daily Introduction')
            ->assertSee('Completed with failures')
            ->assertSee('450')
            ->assertSee('440')
            ->assertSee('10');
    }
}
