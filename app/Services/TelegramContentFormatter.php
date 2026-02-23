<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\DailyContent;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use Illuminate\Support\Collection;

/**
 * Format member content for Telegram (plain text, no HTML).
 */
final class TelegramContentFormatter
{
    private const MAX_MESSAGE_LENGTH = 4000;

    public function formatDayContent(DailyContent $daily, Member $member): string
    {
        $locale = $this->memberLocale($member);
        $parts = [];

        $dayTitle = $this->safeLocalized($daily, 'day_title', $locale) ?? __('app.day_x', ['day' => $daily->day_number]);
        $parts[] = '📖 <b>Day '.$daily->day_number.' of 55</b>';
        $parts[] = $daily->date?->locale('en')->translatedFormat('l, F j, Y') ?? '';

        if ($daily->weeklyTheme) {
            $themeName = localized($daily->weeklyTheme, 'name', $locale) ?? $daily->weeklyTheme->name_en ?? '-';
            $parts[] = '<i>'.$this->escapeHtml($themeName).'</i>';
        }

        $parts[] = '';
        $parts[] = "{$dayTitle}";
        $parts[] = '';

        if (localized($daily, 'bible_reference', $locale)) {
            $parts[] = '📜 <b>'.__('app.bible_reading').'</b>';
            $parts[] = $this->safeLocalized($daily, 'bible_reference', $locale);
            if (localized($daily, 'bible_summary', $locale)) {
                $parts[] = $this->safeLocalized($daily, 'bible_summary', $locale);
            }
            if (localized($daily, 'bible_text', $locale)) {
                $text = localized($daily, 'bible_text', $locale);
                $parts[] = $this->truncateForTelegram($text, 800);
            }
            $parts[] = '';
        }

        if ($daily->mezmurs->isNotEmpty()) {
            $parts[] = '🎵 <b>'.__('app.mezmur').'</b>';
            foreach ($daily->mezmurs as $m) {
                $parts[] = '• '.($this->safeLocalized($m, 'title', $locale) ?? '-');
            }
            $parts[] = '';
        }

        if (localized($daily, 'reflection', $locale)) {
            $parts[] = '💭 <b>'.__('app.reflection').'</b>';
            $parts[] = $this->truncateForTelegram(localized($daily, 'reflection', $locale), 600);
            $parts[] = '';
        }

        $text = implode("\n", $parts);

        return $this->truncateForTelegram($text, self::MAX_MESSAGE_LENGTH);
    }

    public function formatProgressSummary(Member $member): string
    {
        $season = \App\Models\LentSeason::query()->latest('id')->where('is_active', true)->first();
        if (! $season) {
            return __('app.no_active_season');
        }

        $allDays = \App\Models\DailyContent::where('lent_season_id', $season->id)
            ->where('is_published', true)
            ->orderBy('day_number')
            ->get();

        $activities = Activity::where('lent_season_id', $season->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $customActivities = $member->customActivities()->orderBy('sort_order')->get();
        $totalActivities = $activities->count() + $customActivities->count();

        $allDayIds = $allDays->pluck('id');
        $allChecks = MemberChecklist::where('member_id', $member->id)
            ->whereIn('daily_content_id', $allDayIds)
            ->where('completed', true)
            ->get();

        $allCustomChecks = MemberCustomChecklist::where('member_id', $member->id)
            ->whereIn('daily_content_id', $allDayIds)
            ->where('completed', true)
            ->get();

        $today = \Carbon\Carbon::today();
        $pastDays = $allDays->filter(fn ($d) => $d->date && $d->date->lte($today));

        $totalChecks = $allChecks->count() + $allCustomChecks->count();
        $totalPossible = $pastDays->count() * max(1, $totalActivities);
        $overall = ($totalPossible > 0)
            ? (int) round(($totalChecks / $totalPossible) * 100)
            : 0;

        $streak = 0;
        foreach ($pastDays->sortByDesc('day_number') as $day) {
            $done = $allChecks->where('daily_content_id', $day->id)->count()
                + $allCustomChecks->where('daily_content_id', $day->id)->count();
            if ($done > 0) {
                $streak++;
            } else {
                break;
            }
        }

        $locale = $this->memberLocale($member);
        $parts = [];
        $parts[] = '📊 <b>'.__('app.progress').'</b>';
        $parts[] = '';
        $parts[] = __('app.overall_completion', ['pct' => $overall]);
        $parts[] = __('app.streak_days', ['count' => $streak]);
        $parts[] = '';

        $topActivities = $activities->take(5);
        foreach ($topActivities as $a) {
            $done = $allChecks->where('activity_id', $a->id)->count();
            $rate = $pastDays->isNotEmpty() ? (int) round(($done / $pastDays->count()) * 100) : 0;
            $name = $this->safeLocalized($a, 'name', $locale) ?? $a->name ?? '-';
            $bar = $this->progressBar($rate);
            $parts[] = "{$name}: {$bar} {$rate}%";
        }

        return implode("\n", $parts);
    }

    /**
     * @return array{text: string, keyboard: array}
     */
    public function formatChecklistMessage(
        DailyContent $daily,
        Member $member,
        Collection $activities,
        Collection $customActivities,
        Collection $checklist,
        Collection $customChecklist
    ): array {
        $locale = $this->memberLocale($member);
        $parts = [];
        $parts[] = '☑️ <b>'.__('app.checklist').'</b> — Day '.$daily->day_number;
        $parts[] = '';

        $rows = [];
        foreach ($activities as $activity) {
            $entry = $checklist->get($activity->id);
            $done = $entry?->completed ?? false;
            $name = $this->safeLocalized($activity, 'name', $locale) ?? $activity->name ?? '-';
            $checkChar = $done ? '✅' : '⬜';
            $rows[] = [
                'text' => "{$checkChar} {$name}",
                'callback_data' => 'check_a_'.$daily->id.'_'.$activity->id,
            ];
        }
        foreach ($customActivities as $ca) {
            $entry = $customChecklist->get($ca->id);
            $done = $entry?->completed ?? false;
            $checkChar = $done ? '✅' : '⬜';
            $name = $this->escapeHtml($ca->name ?? '');
            $rows[] = [
                'text' => "{$checkChar} {$name}",
                'callback_data' => 'check_c_'.$daily->id.'_'.$ca->id,
            ];
        }

        $keyboard = ['inline_keyboard' => array_map(fn ($r) => [$r], $rows)];

        $backRow = [['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu']];
        $keyboard['inline_keyboard'][] = $backRow;

        return [
            'text' => implode("\n", $parts),
            'keyboard' => $keyboard,
        ];
    }

    private function memberLocale(Member $member): string
    {
        return in_array($member->locale ?? '', ['en', 'am'], true) ? $member->locale : 'en';
    }

    private function truncateForTelegram(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (strlen($text) <= $max) {
            return $this->escapeHtml($text);
        }

        return $this->escapeHtml(substr($text, 0, $max - 3)).'...';
    }

    private function safeLocalized(object $model, string $baseAttr, ?string $locale = null): ?string
    {
        $val = localized($model, $baseAttr, $locale);

        return $val !== null ? $this->escapeHtml($val) : null;
    }

    private function escapeHtml(string $s): string
    {
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $s);
    }

    private function progressBar(int $pct): string
    {
        $filled = (int) round($pct / 10);
        $empty = 10 - $filled;

        return '['.str_repeat('█', $filled).str_repeat('░', $empty).']';
    }
}
