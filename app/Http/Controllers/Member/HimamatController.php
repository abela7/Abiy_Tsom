<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\HimamatDay;
use App\Models\LentSeason;
use App\Models\MemberHimamatPreference;
use App\Services\HimamatSynaxariumService;
use App\Services\HimamatTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HimamatController extends Controller
{
    public function index(HimamatTimelineService $timeline): RedirectResponse|View
    {
        $season = LentSeason::active();
        $landing = $timeline->resolveLandingTarget($season, publishedOnly: true);

        if (! $season || ! $landing) {
            return view('member.himamat.unavailable', [
                'member' => request()->attributes->get('member'),
            ]);
        }

        return redirect()->route('member.himamat.slot', [
            'day' => $landing['day']->slug,
            'slot' => $landing['slot']->slot_key,
        ]);
    }

    public function preferences(Request $request): View
    {
        $member = $request->attributes->get('member');
        $season = LentSeason::active();

        if (! $member || ! $season) {
            return view('member.himamat.unavailable', compact('member'));
        }

        $preferences = MemberHimamatPreference::query()->firstOrCreate(
            [
                'member_id' => $member->id,
                'lent_season_id' => $season->id,
            ],
            MemberHimamatPreference::defaultValues()
        );

        $days = HimamatDay::query()
            ->where('lent_season_id', $season->id)
            ->where('is_published', true)
            ->with(['publishedSlots'])
            ->orderBy('date')
            ->orderBy('sort_order')
            ->get();

        $slotDefinitions = collect(config('himamat.slots', []))
            ->map(fn (array $slot): array => [
                'key' => (string) ($slot['key'] ?? ''),
                'time' => substr((string) ($slot['time'] ?? ''), 0, 5),
                'title' => (string) ($slot['default_slot_header_en'] ?? ''),
            ]);

        return view('member.himamat.preferences', [
            'member' => $member,
            'season' => $season,
            'preferences' => $preferences,
            'days' => $days,
            'slotDefinitions' => $slotDefinitions,
        ]);
    }

    public function day(
        Request $request,
        string $day,
        HimamatTimelineService $timeline
    ): RedirectResponse {
        $member = $request->attributes->get('member');
        $season = LentSeason::active();

        $himamatDay = $this->resolvePublishedDay($season?->id, $day);
        if (! $member || ! $season || ! $himamatDay) {
            abort(404);
        }

        $targetSlot = $timeline->defaultSlotKeyForDay($himamatDay);

        return redirect()->route('member.himamat.slot', [
            'day' => $himamatDay->slug,
            'slot' => $targetSlot,
        ]);
    }

    public function slot(
        Request $request,
        string $day,
        string $slot,
        HimamatTimelineService $timeline,
        HimamatSynaxariumService $synaxarium
    ): View|RedirectResponse {
        $member = $request->attributes->get('member');
        $season = LentSeason::active();

        $himamatDay = $this->resolvePublishedDay($season?->id, $day);
        if (! $member || ! $season || ! $himamatDay) {
            abort(404);
        }

        if (! $himamatDay->slots->contains('slot_key', $slot)) {
            return redirect()->route('member.himamat.day', ['day' => $himamatDay->slug]);
        }

        $timelineData = $timeline->buildTimeline($himamatDay, $slot);
        $publishedDays = HimamatDay::query()
            ->where('lent_season_id', $season->id)
            ->where('is_published', true)
            ->orderBy('date')
            ->orderBy('sort_order')
            ->get()
            ->values();

        $dayIndex = $publishedDays->search(fn (HimamatDay $item): bool => $item->id === $himamatDay->id);
        $previousDay = $dayIndex !== false && $dayIndex > 0 ? $publishedDays->get($dayIndex - 1) : null;
        $nextDay = $dayIndex !== false ? $publishedDays->get($dayIndex + 1) : null;
        $ethDateInfo = $synaxarium->resolveDateInfo($himamatDay, app()->getLocale());

        return view('member.himamat.day', [
            'member' => $member,
            'day' => $himamatDay,
            'timeline' => $timelineData,
            'ethDateInfo' => $ethDateInfo,
            'previousDay' => $previousDay,
            'nextDay' => $nextDay,
            'publicPreview' => false,
        ]);
    }

    private function resolvePublishedDay(?int $seasonId, string $slug): ?HimamatDay
    {
        if (! $seasonId) {
            return null;
        }

        return HimamatDay::query()
            ->where('lent_season_id', $seasonId)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with([
                'slots' => fn ($query) => $query
                    ->where('is_published', true)
                    ->with(['resources' => fn ($resourceQuery) => $resourceQuery->orderBy('sort_order')])
                    ->orderBy('slot_order'),
                'faqs' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->first();
    }
}
