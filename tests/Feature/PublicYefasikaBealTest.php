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
        $response->assertSee('fasika-page', false);
        $response->assertSee(__('app.fasika_banner_main'), false);
    }
}
