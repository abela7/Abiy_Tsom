<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\WeeklyTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberDayRouteResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_day_route_uses_trailing_id_from_compound_slug(): void
    {
        $member = $this->createMember('m');
        $daily = $this->createPublishedDay();

        $this->get('/m/'.$member->token.'/day/99-'.$daily->id)
            ->assertRedirect($daily->memberDayUrl($member->token));
    }

    public function test_member_commemorations_route_uses_trailing_id_from_compound_slug(): void
    {
        $member = $this->createMember('n');
        $daily = $this->createPublishedDay();

        $this->get('/m/'.$member->token.'/day/99-'.$daily->id.'/commemorations')
            ->assertRedirect($daily->memberCommemorationsUrl($member->token));
    }

    private function createPublishedDay(): DailyContent
    {
        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 1,
            'name_en' => 'Zewerede',
            'meaning' => 'He who descended from above',
            'week_start_date' => '2026-02-15',
            'week_end_date' => '2026-02-21',
        ]);

        return DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 36,
            'date' => '2026-03-23',
            'day_title_en' => 'Test Day',
            'is_published' => true,
        ]);
    }

    private function createMember(string $fill): Member
    {
        return Member::create([
            'baptism_name' => 'Member '.$fill,
            'token' => str_repeat($fill, 64),
            'locale' => 'en',
            'theme' => 'sepia',
        ]);
    }
}
