<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyContent;
use App\Models\DailyContentMezmur;
use App\Models\HimamatDay;
use App\Models\HimamatDayFaq;
use App\Models\HimamatSlot;
use App\Models\Lectionary;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\WeeklyTheme;
use App\Services\EthiopianCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberHimamatDailyVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_himamat_days_use_linked_daily_variant_and_hide_daily_bible_and_lectionary(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-06 09:00:00', 'Europe/London'));

        $season = $this->createSeason();
        $theme = $this->createTheme($season);
        $member = $this->createMember();
        $daily = $this->createDaily($season, $theme, 50, '2026-04-06');

        DailyContentMezmur::create([
            'daily_content_id' => $daily->id,
            'title_en' => 'Hosanna Hymn',
            'url_en' => 'https://example.com/hymn',
            'sort_order' => 1,
        ]);

        $dateInfo = app(EthiopianCalendarService::class)->getDateInfo($daily->date, 'en');
        Lectionary::create([
            'month' => $dateInfo['ethiopian_date']['month'],
            'day' => $dateInfo['ethiopian_date']['day'],
            'title_en' => 'Hidden Lectionary',
            'gospel_book_en' => 'Matthew',
            'gospel_chapter' => 24,
        ]);

        $himamatDay = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'spiritual_meaning_en' => 'The fig tree reveals the call to repentance.',
            'ritual_guide_intro_en' => 'Keep watch in silence and prayer.',
            'synaxarium_title_en' => 'Synaxarium of Holy Monday',
            'synaxarium_text_en' => 'The church remembers the appointed saints even during Passion Week.',
            'is_published' => true,
        ]);

        HimamatDayFaq::create([
            'himamat_day_id' => $himamatDay->id,
            'sort_order' => 1,
            'question_en' => 'Why do we bow during Himamat?',
            'answer_en' => 'To stand in repentance and reverence before the Lord.',
        ]);

        HimamatSlot::create([
            'himamat_day_id' => $himamatDay->id,
            'slot_key' => 'intro',
            'slot_order' => 1,
            'scheduled_time_london' => '07:00:00',
            'slot_header_en' => 'Daily Introduction',
            'reading_reference_en' => 'Mark 11:12-26',
            'reading_text_en' => 'The cursing of the fig tree.',
            'reminder_header_en' => 'Holy Monday Introduction',
            'is_published' => true,
        ]);

        $this->get('/m/'.$member->token.'/day/50-'.$daily->id)
            ->assertOk()
            ->assertSee('Holy Monday')
            ->assertSee('Sacred Timeline')
            ->assertSee('Hosanna Hymn')
            ->assertSee('Synaxarium Heading')
            ->assertSee('Daily Message Heading')
            ->assertSeeInOrder([
                'Hosanna Hymn',
                'Holy Monday',
                'Synaxarium Heading',
                'Daily Message Heading',
            ])
            ->assertDontSeeText('This daily Bible block should disappear for Himamat days.')
            ->assertDontSeeText('John 12:1-11')
            ->assertDontSeeText('Hidden Lectionary');
    }

    public function test_non_himamat_days_keep_standard_daily_bible_and_lectionary_sections(): void
    {
        $season = $this->createSeason();
        $theme = $this->createTheme($season);
        $member = $this->createMember('b');
        $daily = $this->createDaily($season, $theme, 49, '2026-04-05');

        $dateInfo = app(EthiopianCalendarService::class)->getDateInfo($daily->date, 'en');
        Lectionary::create([
            'month' => $dateInfo['ethiopian_date']['month'],
            'day' => $dateInfo['ethiopian_date']['day'],
            'title_en' => 'Visible Lectionary',
            'gospel_book_en' => 'John',
            'gospel_chapter' => 12,
        ]);

        $this->get('/m/'.$member->token.'/day/49-'.$daily->id)
            ->assertOk()
            ->assertSee('Bible Reading')
            ->assertSee('Visible Lectionary');
    }

    private function createSeason(): LentSeason
    {
        return LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);
    }

    private function createTheme(LentSeason $season): WeeklyTheme
    {
        return WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Hosanna',
            'meaning' => 'The final week before Easter.',
            'week_start_date' => '2026-04-05',
            'week_end_date' => '2026-04-12',
        ]);
    }

    private function createMember(string $fill = 'a'): Member
    {
        return Member::create([
            'baptism_name' => 'Member '.$fill,
            'token' => str_repeat($fill, 64),
            'locale' => 'en',
            'theme' => 'sepia',
        ]);
    }

    private function createDaily(LentSeason $season, WeeklyTheme $theme, int $dayNumber, string $date): DailyContent
    {
        return DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => $dayNumber,
            'date' => $date,
            'day_title_en' => 'Test Day '.$dayNumber,
            'bible_reference_en' => 'John 12:1-11',
            'bible_text_en' => 'This daily Bible block should disappear for Himamat days.',
            'sinksar_title_en' => 'Synaxarium Heading',
            'sinksar_text_en' => 'Synaxarium content is still shown.',
            'reflection_title_en' => 'Daily Message Heading',
            'reflection_en' => 'Daily message content is still shown.',
            'is_published' => true,
        ]);
    }
}
