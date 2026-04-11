<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyContent;
use App\Models\FasikaGreetingShare;
use App\Models\Lectionary;
use App\Models\LentSeason;
use App\Models\WeeklyTheme;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicYefasikaBealTest extends TestCase
{
    use RefreshDatabase;

    public function test_yefasika_beal_page_is_public_and_ok(): void
    {
        $response = $this->get(route('public.yefasika-beal'));

        $response->assertOk();
        $response->assertSee('ybb-page', false);
        $response->assertSee('ybb-fullbleed', false);
        $response->assertSee('ybb-particles', false);
        $response->assertSee(__('app.fasika_banner_main'), false);
        $response->assertSee(rtrim((string) config('app.parish_website_url'), '/').'/', false);
        $response->assertSee('property="og:title" content="'.e(__('app.yefasika_beal_og_title')).'"', false);
        $response->assertSee('property="og:image" content="'.e(asset('images/Jesus_In_Eastern.avif')).'"', false);
    }

    public function test_member_day_fasika_path_serves_same_page(): void
    {
        $response = $this->get('/member/day/fasika');

        $response->assertOk();
        $response->assertSee(__('app.fasika_banner_main'), false);
    }

    public function test_member_day_capital_f_redirects_to_lowercase(): void
    {
        $this->get('/member/day/Fasika')
            ->assertRedirect('/member/day/fasika');
    }

    public function test_can_create_personalized_fasika_share_link(): void
    {
        $response = $this->postJson(route('public.yefasika-beal.store'), [
            'sender_name' => '  Abel   Teklu  ',
        ]);

        $response->assertOk()
            ->assertJsonPath('sender_name', 'Abel Teklu')
            ->assertJsonPath('share_text', __('app.yefasika_beal_share_text'));

        $share = FasikaGreetingShare::query()->firstOrFail();

        $this->assertSame('Abel Teklu', $share->sender_name);
        $this->assertSame('abel teklu', $share->sender_name_normalized);
        $this->assertSame(route('public.yefasika-beal.share', $share), $response->json('share_url'));
    }

    public function test_personalized_share_page_shows_sender_and_tracks_opens(): void
    {
        $share = FasikaGreetingShare::query()->create([
            'share_token' => 'fasikaabel1234567890',
            'sender_name' => 'አቤል',
            'sender_name_normalized' => 'አቤል',
        ]);

        $response = $this->get(route('public.yefasika-beal.share', $share));

        $response->assertOk()
            ->assertSee(__('app.yefasika_beal_short_greeting_line_one'))
            ->assertSee(__('app.yefasika_beal_from_name', ['name' => 'አቤል']))
            ->assertSee('property="og:title" content="'.e(__('app.yefasika_beal_og_title')).'"', false)
            ->assertSee('property="og:image" content="'.e(asset('images/Jesus_In_Eastern.avif')).'"', false);

        $share->refresh();

        $this->assertSame(1, $share->open_count);
        $this->assertNotNull($share->first_opened_at);
        $this->assertNotNull($share->last_opened_at);
    }

    public function test_public_fasika_page_can_show_additional_daily_content(): void
    {
        $season = LentSeason::query()->create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 56,
            'is_active' => true,
        ]);

        $week = WeeklyTheme::query()->create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Holy Week',
            'name_am' => 'ሕማማት',
            'meaning' => 'Holy Week',
            'week_start_date' => '2026-04-06',
            'week_end_date' => '2026-04-12',
        ]);

        $daily = DailyContent::query()->create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $week->id,
            'day_number' => 56,
            'date' => Carbon::parse(
                config('app.easter_date', '2026-04-12 03:00'),
                config('app.easter_timezone', 'Europe/London')
            )->toDateString(),
            'day_title' => 'Fasika',
            'day_title_en' => 'Fasika',
            'day_title_am' => 'ፋሲካ',
            'bible_reference' => 'John 20:1-18',
            'bible_reference_en' => 'John 20:1-18',
            'bible_reference_am' => 'ዮሐንስ 20፥1-18',
            'bible_summary' => 'Resurrection reading',
            'bible_summary_en' => 'Resurrection reading',
            'bible_summary_am' => 'የትንሣኤ ንባብ',
            'bible_text_en' => 'Mary Magdalene came to the tomb early in the morning.',
            'bible_text_am' => 'ማርያም መግደላዊት በማለዳ ወደ መቃብር መጣች።',
            'is_published' => true,
        ]);

        $daily->mezmurs()->create([
            'title_en' => 'Christ is risen',
            'title_am' => 'ክርስቶስ ተነስቷል',
            'description_en' => 'Selected hymn for Easter day',
            'description_am' => 'ለትንሣኤ የተመረጠ መዝሙር',
            'lyrics_en' => 'Christ is risen from the dead.',
            'lyrics_am' => 'ክርስቶስ ከሙታን ተነስቷል።',
            'sort_order' => 0,
        ]);

        $ethDateInfo = app(EthiopianCalendarService::class)->getDateInfo($daily->date, 'am');

        Lectionary::query()->create([
            'month' => data_get($ethDateInfo, 'ethiopian_date.month'),
            'day' => data_get($ethDateInfo, 'ethiopian_date.day'),
            'title_am' => 'የትንሣኤ ግጻዌ',
            'title_en' => 'Easter Lectionary',
            'description_am' => 'የቀኑ ንባቦች',
            'description_en' => 'Readings for the day',
            'gospel_book_am' => 'ዮሐንስ',
            'gospel_book_en' => 'John',
            'gospel_chapter' => 20,
            'gospel_verses' => '1-18',
            'gospel_text_am' => 'በሳምንቱ መጀመሪያ ቀን ማለዳ...',
            'gospel_text_en' => 'On the first day of the week...',
        ]);

        $response = $this->get(route('public.yefasika-beal'));

        $response->assertOk()
            ->assertSee(__('app.yefasika_beal_additional_content_title'))
            ->assertSee(__('app.fasika_bible_reading_title'))
            ->assertSee(__('app.lectionary'))
            ->assertSee(__('app.fasika_selected_hymn_title'))
            ->assertSee('ዮሐንስ 20፥1-18')
            ->assertSee('የትንሣኤ ግጻዌ')
            ->assertSee('ክርስቶስ ተነስቷል');
    }
}
