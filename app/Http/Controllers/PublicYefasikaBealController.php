<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public Easter greeting card (Yefasika Beal). Uses public-only background CSS;
 * member in-app Fasika day keeps its own shell in {@see \App\Http\Controllers\Member\HomeController} day view.
 */
class PublicYefasikaBealController extends Controller
{
    public function show(Request $request): View
    {
        $pageTitle = __('app.yefasika_beal_page_title').' — '.__('app.app_name');
        $ogTitle = __('app.yefasika_beal_og_title');
        $ogDescription = __('app.yefasika_beal_og_description');
        $ogUrl = route('member.day.fasika');
        $shareUrl = $request->fullUrl();

        return view('public.yefasika-beal', compact(
            'pageTitle',
            'ogTitle',
            'ogDescription',
            'ogUrl',
            'shareUrl',
        ));
    }
}
