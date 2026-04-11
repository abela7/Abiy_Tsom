<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FasikaGreetingShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public Easter greeting card (Yefasika Beal). Uses public-only background CSS;
 * member in-app Fasika day keeps its own shell in Member\HomeController day view.
 */
class PublicYefasikaBealController extends Controller
{
    public function show(Request $request, ?FasikaGreetingShare $share = null): View
    {
        if ($share) {
            $share->recordOpen($request);
        }

        $pageTitle = __('app.yefasika_beal_page_title').' - '.__('app.app_name');
        $shareText = __('app.yefasika_beal_share_text');
        /* Social preview title stays consistent for WhatsApp / Telegram (brief). */
        $ogTitle = __('app.yefasika_beal_og_title');
        $ogDescription = $shareText;
        $ogUrl = $request->fullUrl();
        $ogImage = asset('images/Jesus_In_Eastern.avif');
        $shareUrl = $request->fullUrl();

        return view('public.yefasika-beal', compact(
            'pageTitle',
            'ogTitle',
            'ogDescription',
            'ogUrl',
            'ogImage',
            'shareUrl',
            'shareText',
            'share',
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:120', 'regex:/.*\S.*/u'],
        ]);

        $senderName = Str::of((string) $validated['sender_name'])->squish()->value();

        $share = FasikaGreetingShare::create([
            'share_token' => $this->generateShareToken(),
            'sender_name' => $senderName,
            'sender_name_normalized' => Str::lower($senderName),
            'creator_ip' => $request->ip(),
            'creator_user_agent' => (string) $request->userAgent(),
        ]);

        $shareUrl = route('public.yefasika-beal.share', $share);
        $shareText = __('app.yefasika_beal_share_text');

        if ($request->expectsJson()) {
            return response()->json([
                'share_url' => $shareUrl,
                'share_text' => $shareText,
                'sender_name' => $share->sender_name,
            ]);
        }

        return redirect($shareUrl);
    }

    private function generateShareToken(): string
    {
        do {
            $token = Str::random(20);
        } while (FasikaGreetingShare::query()->where('share_token', $token)->exists());

        return $token;
    }
}
