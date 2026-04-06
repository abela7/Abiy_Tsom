<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\LentSeason;
use App\Models\WeeklyTheme;
use App\Services\HimamatScaffoldService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HimamatScaffoldAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_scaffold_aligns_himamat_days_to_daily_days_fifty_to_fifty_five(): void
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
            'meaning' => 'Passion Week',
            'week_start_date' => '2026-04-05',
            'week_end_date' => '2026-04-11',
        ]);

        foreach ([
            50 => '2026-04-06',
            51 => '2026-04-07',
            52 => '2026-04-08',
            53 => '2026-04-09',
            54 => '2026-04-10',
            55 => '2026-04-11',
        ] as $dayNumber => $date) {
            DailyContent::create([
                'lent_season_id' => $season->id,
                'weekly_theme_id' => $theme->id,
                'day_number' => $dayNumber,
                'date' => $date,
                'is_published' => false,
            ]);
        }

        HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'hosanna-sunday',
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Custom Monday Title',
            'is_published' => false,
        ]);

        HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 2,
            'date' => '2026-04-07',
            'title_en' => 'Holy Monday',
            'is_published' => false,
        ]);

        HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-tuesday',
            'sort_order' => 3,
            'date' => '2026-04-08',
            'title_en' => 'Holy Tuesday',
            'is_published' => false,
        ]);

        HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-wednesday',
            'sort_order' => 4,
            'date' => '2026-04-09',
            'title_en' => 'Holy Wednesday',
            'is_published' => false,
        ]);

        HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-thursday',
            'sort_order' => 5,
            'date' => '2026-04-10',
            'title_en' => 'Holy Thursday',
            'is_published' => false,
        ]);

        HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'good-friday',
            'sort_order' => 6,
            'date' => '2026-04-11',
            'title_en' => 'Good Friday',
            'is_published' => false,
        ]);

        HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-saturday',
            'sort_order' => 7,
            'date' => '2026-04-12',
            'title_en' => 'Holy Saturday',
            'is_published' => false,
        ]);

        app(HimamatScaffoldService::class)->scaffoldActiveSeason();

        $this->assertDatabaseHas('himamat_days', [
            'lent_season_id' => $season->id,
            'date' => '2026-04-06',
            'slug' => 'holy-monday',
            'title_en' => 'Custom Monday Title',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('himamat_days', [
            'lent_season_id' => $season->id,
            'date' => '2026-04-07',
            'slug' => 'holy-tuesday',
            'title_en' => 'Holy Tuesday',
            'sort_order' => 2,
        ]);

        $this->assertDatabaseHas('himamat_days', [
            'lent_season_id' => $season->id,
            'date' => '2026-04-11',
            'slug' => 'holy-saturday',
            'title_en' => 'Holy Saturday',
            'sort_order' => 6,
        ]);
    }
}
