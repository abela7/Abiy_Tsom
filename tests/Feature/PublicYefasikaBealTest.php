<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class PublicYefasikaBealTest extends TestCase
{
    public function test_yefasika_beal_page_is_public_and_ok(): void
    {
        $response = $this->get(route('public.yefasika-beal'));

        $response->assertOk();
        $response->assertSee('ybb-page', false);
        $response->assertSee('ybb-fullbleed', false);
        $response->assertSee('ybb-particles', false);
        $response->assertSee(__('app.fasika_banner_main'), false);
        $response->assertSee(rtrim((string) config('app.parish_website_url'), '/').'/', false);
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
}
