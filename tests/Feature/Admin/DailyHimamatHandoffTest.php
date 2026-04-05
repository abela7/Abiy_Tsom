<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\User;
use App\Models\WeeklyTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyHimamatHandoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_himamat_daily_edit_redirects_to_himamat_editor_for_first_steps(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        [$daily, $himamatDay] = $this->createLinkedHimamatDaily();

        $this->actingAs($admin)
            ->get(route('admin.daily.edit', ['daily' => $daily]))
            ->assertRedirect(route('admin.himamat.edit', [
                'day' => $himamatDay->id,
                'daily' => $daily->id,
                'return_step' => 3,
            ]));

        $this->actingAs($admin)
            ->get(route('admin.daily.edit', ['daily' => $daily, 'step' => 2]))
            ->assertRedirect(route('admin.himamat.edit', [
                'day' => $himamatDay->id,
                'daily' => $daily->id,
                'return_step' => 3,
            ]));
    }

    public function test_himamat_daily_later_steps_show_link_back_to_himamat_editor(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        [$daily, $himamatDay] = $this->createLinkedHimamatDaily();

        $response = $this->actingAs($admin)
            ->get(route('admin.daily.edit', ['daily' => $daily, 'step' => 3]));

        $response->assertOk()
            ->assertSee('This Passion Week day uses the Himamat editor')
            ->assertSee(route('admin.himamat.edit', [
                'day' => $himamatDay->id,
                'daily' => $daily->id,
                'return_step' => 3,
            ]));
    }

    public function test_himamat_editor_from_daily_context_shows_continue_button(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        [$daily, $himamatDay] = $this->createLinkedHimamatDaily();

        $this->actingAs($admin)
            ->get(route('admin.himamat.edit', [
                'day' => $himamatDay->id,
                'daily' => $daily->id,
                'return_step' => 3,
            ]))
            ->assertOk()
            ->assertSee('Continue Daily Content')
            ->assertSee(route('admin.daily.edit', ['daily' => $daily->id, 'step' => 3]));
    }

    /**
     * @return array{0: DailyContent, 1: HimamatDay}
     */
    private function createLinkedHimamatDaily(): array
    {
        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Hosanna',
            'name_am' => 'ሆሣዕና',
            'meaning' => 'Hosanna week',
            'week_start_date' => '2026-04-05',
            'week_end_date' => '2026-04-11',
        ]);

        $daily = DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 50,
            'date' => '2026-04-06',
            'day_title_en' => 'Holy Monday',
            'is_published' => false,
        ]);

        $himamatDay = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'is_published' => false,
        ]);

        foreach (config('himamat.slots', []) as $slotDefinition) {
            HimamatSlot::create([
                'himamat_day_id' => $himamatDay->id,
                'slot_key' => $slotDefinition['key'],
                'slot_order' => $slotDefinition['order'],
                'scheduled_time_london' => $slotDefinition['time'],
                'slot_header_en' => $slotDefinition['default_slot_header_en'],
                'reminder_header_en' => $slotDefinition['default_reminder_header_en'],
                'is_published' => false,
            ]);
        }

        return [$daily, $himamatDay];
    }
}
