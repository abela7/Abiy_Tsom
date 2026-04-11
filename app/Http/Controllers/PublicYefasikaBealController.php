<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyContent;
use App\Models\FasikaGreetingShare;
use App\Models\Lectionary;
use App\Models\LentSeason;
use App\Models\Translation;
use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
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
        app()->setLocale('am');
        Carbon::setLocale('am');
        Translation::loadFromDb('am');

        if ($share) {
            $share->recordOpen($request);
        }

        $fasikaDaily = $this->resolveFasikaDailyContent();
        $fasikaLectionary = $this->resolveFasikaLectionary($fasikaDaily);

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
            'fasikaDaily',
            'fasikaLectionary',
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

    private function resolveFasikaDailyContent(): ?DailyContent
    {
        $season = LentSeason::active();

        $baseQuery = DailyContent::query()
            ->with(['mezmurs'])
            ->where('is_published', true);

        if ($season) {
            $baseQuery->where('lent_season_id', $season->id);
        }

        $easterDate = Carbon::parse(
            config('app.easter_date', '2026-04-12 03:00'),
            config('app.easter_timezone', 'Europe/London')
        )->toDateString();

        return (clone $baseQuery)
            ->whereDate('date', $easterDate)
            ->first()
            ?? (clone $baseQuery)
                ->where('day_number', 56)
                ->latest('date')
                ->first();
    }

    private function resolveFasikaLectionary(?DailyContent $daily): ?Lectionary
    {
        if (! $daily?->date) {
            return null;
        }

        $ethDateInfo = app(EthiopianCalendarService::class)->getDateInfo($daily->date, app()->getLocale());
        $month = data_get($ethDateInfo, 'ethiopian_date.month');
        $day = data_get($ethDateInfo, 'ethiopian_date.day');

        if (! $month || ! $day) {
            return null;
        }

        return Lectionary::query()
            ->where('month', $month)
            ->where('day', $day)
            ->first();
    }
}
