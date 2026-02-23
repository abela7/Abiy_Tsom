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
 * Format member content for Telegram. Uses HTML with expandable sections
 * for a modern, sectioned UI. All user content is escaped for safety.
 */
final class TelegramContentFormatter
{
    private const MAX_MESSAGE_LENGTH = 4080;

    private const DIVIDER = '▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬';

    /**
     * @return array{text: string, use_html: bool}
     */
    public function formatDayContent(DailyContent $daily, Member $member): array
    {
        $locale = $this->memberLocale($member);
        $parts = [];

        $dayTitle = $this->h(localized($daily, 'day_title', $locale) ?? __('app.day_x', ['day' => $daily->day_number]));
        $dateStr = $daily->date?->locale('en')->translatedFormat('l, F j, Y') ?? '';

        $parts[] = '<b>📖 Day '.$daily->day_number.' of 55</b>';
        $parts[] = '<i>'.$dateStr.'</i>';
        if ($daily->weeklyTheme) {
            $themeName = $this->h(localized($daily->weeklyTheme, 'name', $locale) ?? $daily->weeklyTheme->name_en ?? '-');
            $parts[] = '<i>'.$themeName.'</i>';
        }
        $parts[] = '';
        $parts[] = $dayTitle;
        $parts[] = '';
        $parts[] = self::DIVIDER;

        if (localized($daily, 'bible_reference', $locale)) {
            $parts[] = '<b>📜 '.__('app.bible_reading').'</b>';
            $parts[] = $this->h(localized($daily, 'bible_reference', $locale));
            if (localized($daily, 'bible_summary', $locale)) {
                $parts[] = $this->h(localized($daily, 'bible_summary', $locale));
            }
            $bibleText = localized($daily, 'bible_text', $locale);
            if ($bibleText) {
                $escaped = $this->h($bibleText);
                $parts[] = $this->expandableQuote($escaped, 1200);
            }
            $parts[] = '';
            $parts[] = self::DIVIDER;
        }

        if ($daily->mezmurs->isNotEmpty()) {
            $parts[] = '<b>🎵 '.__('app.mezmur').'</b>';
            foreach ($daily->mezmurs as $m) {
                $title = $this->h(localized($m, 'title', $locale) ?? '-');
                $parts[] = '• '.$title;
            }
            $parts[] = '';
            $parts[] = self::DIVIDER;
        }

        if (localized($daily, 'sinksar_title', $locale)) {
            $parts[] = '<b>📿 '.__('app.sinksar').'</b>';
            $parts[] = $this->h(localized($daily, 'sinksar_title', $locale));
            if (localized($daily, 'sinksar_description', $locale)) {
                $parts[] = $this->h(localized($daily, 'sinksar_description', $locale));
            }
            $parts[] = '';
            $parts[] = self::DIVIDER;
        }

        if (localized($daily, 'reflection', $locale)) {
            $parts[] = '<b>💭 '.__('app.reflection').'</b>';
            $reflection = $this->h(localized($daily, 'reflection', $locale));
            $parts[] = $this->expandableQuote($reflection, 1000);
        }

        $text = implode("\n", $parts);

        return [
            'text' => mb_substr($text, 0, self::MAX_MESSAGE_LENGTH),
            'use_html' => true,
        ];
    }

    /**
     * Wrap text in blockquote. Long text uses expandable for collapsed UI (tap to expand).
     */
    private function expandableQuote(string $escapedText, int $maxChars): string
    {
        $text = trim($escapedText);
        if ($text === '') {
            return '';
        }
        $lines = preg_split('/\n\s*|\s{2,}/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($lines === []) {
            $lines = [$text];
        }
        $singleLine = implode(' ', $lines);
        if (mb_strlen($singleLine) <= 150) {
            return '<blockquote>'.$singleLine.'</blockquote>';
        }
        $withNewlines = implode("\n", array_slice($lines, 0, 60));
        if (count($lines) >= 3) {
            return '<blockquote expandable>'.$this->truncate($withNewlines, $maxChars).'</blockquote>';
        }
        $sentences = preg_split('/(?<=[.!?።])\s+/u', $singleLine, -1, PREG_SPLIT_NO_EMPTY) ?: [$singleLine];
        if (count($sentences) >= 3) {
            $withNewlines = implode("\n", array_slice($sentences, 0, 40));

            return '<blockquote expandable>'.$this->truncate($withNewlines, $maxChars).'</blockquote>';
        }

        return '<blockquote>'.$this->truncate($singleLine, $maxChars).'</blockquote>';
    }

    /** Escape for Telegram HTML parse_mode: & < > " */
    private function h(?string $s): string
    {
        if ($s === null || $s === '') {
            return '';
        }
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);

        return str_replace(['&', '<', '>', '"'], ['&amp;', '&lt;', '&gt;', '&quot;'], trim($s));
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
        $parts[] = '📊 '.__('app.progress');
        $parts[] = '';
        $parts[] = __('app.overall_completion', ['pct' => $overall]);
        $parts[] = __('app.streak_days', ['count' => $streak]);
        $parts[] = '';

        $topActivities = $activities->take(5);
        foreach ($topActivities as $a) {
            $done = $allChecks->where('activity_id', $a->id)->count();
            $rate = $pastDays->isNotEmpty() ? (int) round(($done / $pastDays->count()) * 100) : 0;
            $name = $this->safeText(localized($a, 'name', $locale) ?? $a->name ?? '-');
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
        $parts[] = '☑️ '.__('app.checklist').' — Day '.$daily->day_number;
        $parts[] = '';

        $rows = [];
        foreach ($activities as $activity) {
            $entry = $checklist->get($activity->id);
            $done = $entry?->completed ?? false;
            $name = $this->safeText(localized($activity, 'name', $locale) ?? $activity->name ?? '-');
            $checkChar = $done ? '✅' : '⬜';
            $cb = $this->callbackData('check_a', (string) $daily->id, (string) $activity->id);
            $rows[] = [
                'text' => "{$checkChar} {$name}",
                'callback_data' => $cb,
            ];
        }
        foreach ($customActivities as $ca) {
            $entry = $customChecklist->get($ca->id);
            $done = $entry?->completed ?? false;
            $checkChar = $done ? '✅' : '⬜';
            $name = $this->safeText($ca->name ?? '');
            $cb = $this->callbackData('check_c', (string) $daily->id, (string) $ca->id);
            $rows[] = [
                'text' => "{$checkChar} {$name}",
                'callback_data' => $cb,
            ];
        }

        $keyboard = ['inline_keyboard' => array_map(fn ($r) => [$r], $rows)];
        $keyboard['inline_keyboard'][] = [['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu']];

        return [
            'text' => implode("\n", $parts),
            'keyboard' => $keyboard,
        ];
    }

    /** Ensure callback_data ≤ 64 bytes (Telegram limit). */
    private function callbackData(string $prefix, string $id1, string $id2): string
    {
        $cb = "{$prefix}_{$id1}_{$id2}";
        if (strlen($cb) <= 64) {
            return $cb;
        }

        return substr($cb, 0, 64);
    }

    private function memberLocale(Member $member): string
    {
        return in_array($member->locale ?? '', ['en', 'am'], true) ? $member->locale : 'en';
    }

    private function truncate(string $text, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim($text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 3).'...';
    }

    /** Sanitize user content for plain-text display (strip control chars, limit length). */
    private function safeText(?string $s): string
    {
        if ($s === null || $s === '') {
            return '-';
        }
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);

        return trim($s) ?: '-';
    }

    private function progressBar(int $pct): string
    {
        $filled = (int) round($pct / 10);
        $empty = 10 - $filled;

        return '['.str_repeat('█', $filled).str_repeat('░', $empty).']';
    }
}
