<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LentSeason;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LentSeasonActiveSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_returns_latest_active_season_when_multiple_rows_are_flagged(): void
    {
        LentSeason::create([
            'year' => 2025,
            'start_date' => '2025-03-01',
            'end_date' => '2025-04-20',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $latest = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $this->assertNotNull(LentSeason::active());
        $this->assertTrue(LentSeason::active()->is($latest));
    }
}
