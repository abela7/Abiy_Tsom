<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FasikaGreetingShare;
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
}
