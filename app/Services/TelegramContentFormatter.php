<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\DailyContent;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Format member content for Telegram. Uses HTML with expandable sections
 * for a modern, sectioned UI. All user content is escaped for safety.
 */
final class TelegramContentFormatter
{
    private const MAX_MESSAGE_LENGTH = 4080;

    private const DIVIDER = '▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬';

    /** Section codes for callback_data (≤64 bytes). */
    private const SECTIONS = [
        'b' => 'bible',
        'm' => 'mezmur',
        's' => 'sinksar',
        'k' => 'books',
        'r' => 'reference',
        'f' => 'reflection',
    ];

    /**
     * Format a single Today section with navigation. For YouTube content,
     * uses Web App buttons so user can watch inline without leaving Telegram.
     *
     * @return array{text: string, use_html: bool, keyboard: array}
     */
    public function formatDaySection(DailyContent $daily, Member $member, string $section): array
    {
        $locale = $this->memberLocale($member);
        $dailyId = (string) $daily->id;
        $parts = $this->buildSectionHeader($daily, $member, $locale, $section);

        $content = match ($section) {
            'bible' => $this->sectionBible($daily, $locale),
            'mezmur' => $this->sectionMezmur($daily, $locale),
            'sinksar' => $this->sectionSinksar($daily, $locale),
            'books' => $this->sectionBooks($daily, $locale),
            'reference' => $this->sectionReference($daily, $locale),
            'reflection' => $this->sectionReflection($daily, $locale),
            default => [],
        };

        $parts = array_merge($parts, $content);
        $text = implode("\n", $parts);
        $keyboard = $this->sectionNavKeyboard($daily, $member, $section, $dailyId);

        return [
            'text' => mb_substr($text, 0, self::MAX_MESSAGE_LENGTH),
            'use_html' => true,
            'keyboard' => $keyboard,
        ];
    }

    private function buildSectionHeader(DailyContent $daily, Member $member, string $locale, string $section): array
    {
        $dateStr = $daily->date?->locale('en')->translatedFormat('l, F j, Y') ?? '';
        $parts = [];

        $sectionLabel = $this->sectionLabel($section);
        $parts[] = '<b>▶ '.$sectionLabel.'</b>';
        $parts[] = self::DIVIDER;
        $parts[] = '';
        $parts[] = '<b>📖 Day '.$daily->day_number.' of 55</b>';
        $parts[] = '<i>'.$dateStr.'</i>';
        if ($daily->weeklyTheme) {
            $themeName = $this->h(localized($daily->weeklyTheme, 'name', $locale) ?? $daily->weeklyTheme->name_en ?? '-');
            $parts[] = '<i>'.$themeName.'</i>';
        }
        $dayTitle = $this->h(localized($daily, 'day_title', $locale) ?? __('app.day_x', ['day' => $daily->day_number]));
        $parts[] = '';
        $parts[] = $dayTitle;
        $parts[] = '';
        $parts[] = self::DIVIDER;

        return $parts;
    }

    private function sectionLabel(string $section): string
    {
        return match ($section) {
            'bible' => __('app.telegram_nav_bible'),
            'mezmur' => __('app.telegram_nav_mezmur'),
            'sinksar' => __('app.telegram_nav_sinksar'),
            'books' => __('app.telegram_nav_books'),
            'reference' => __('app.telegram_nav_references'),
            'reflection' => __('app.telegram_nav_reflection'),
            default => $section,
        };
    }

    private function sectionBible(DailyContent $daily, string $locale): array
    {
        $parts = [];
        if (! localized($daily, 'bible_reference', $locale)) {
            $parts[] = __('app.no_content');

            return $parts;
        }
        $parts[] = $this->h(localized($daily, 'bible_reference', $locale));
        $parts[] = '';
        if (localized($daily, 'bible_summary', $locale)) {
            $parts[] = $this->h(localized($daily, 'bible_summary', $locale));
            $parts[] = '';
        }
        $bibleText = localized($daily, 'bible_text', $locale);
        if ($bibleText) {
            $escaped = $this->h($bibleText);
            $parts[] = $this->expandableQuote($escaped, 1200);
        }

        return $parts;
    }

    private function sectionMezmur(DailyContent $daily, string $locale): array
    {
        $parts = [];
        if ($daily->mezmurs->isEmpty()) {
            $parts[] = __('app.no_content');

            return $parts;
        }
        foreach ($daily->mezmurs as $m) {
            $title = $this->h(localized($m, 'title', $locale) ?? '-');
            $parts[] = '  • '.$title;
        }

        return $parts;
    }

    private function sectionSinksar(DailyContent $daily, string $locale): array
    {
        $parts = [];
        if (! localized($daily, 'sinksar_title', $locale)) {
            $parts[] = __('app.no_content');

            return $parts;
        }
        $parts[] = $this->h(localized($daily, 'sinksar_title', $locale));
        $parts[] = '';
        if (localized($daily, 'sinksar_description', $locale)) {
            $parts[] = $this->h(localized($daily, 'sinksar_description', $locale));
        }

        return $parts;
    }

    private function sectionBooks(DailyContent $daily, string $locale): array
    {
        $parts = [];
        if (! $daily->books || $daily->books->isEmpty()) {
            $parts[] = __('app.no_content');

            return $parts;
        }
        foreach ($daily->books as $book) {
            $title = $this->h(localized($book, 'title', $locale));
            if ($title === '') {
                continue;
            }
            $parts[] = '  • '.$title;
            if (localized($book, 'description', $locale)) {
                $parts[] = '    '.$this->h(localized($book, 'description', $locale));
            }
        }

        return $parts;
    }

    private function sectionReference(DailyContent $daily, string $locale): array
    {
        $parts = [];
        if (! $daily->references || $daily->references->isEmpty()) {
            $parts[] = __('app.no_content');

            return $parts;
        }
        foreach ($daily->references as $ref) {
            $name = $this->h(localized($ref, 'name', $locale) ?? '-');
            $parts[] = '  • '.$name;
        }

        return $parts;
    }

    private function sectionReflection(DailyContent $daily, string $locale): array
    {
        $parts = [];
        if (! localized($daily, 'reflection', $locale)) {
            $parts[] = __('app.no_content');

            return $parts;
        }
        $reflection = $this->h(localized($daily, 'reflection', $locale));
        $parts[] = $this->expandableQuote($reflection, 1000);

        return $parts;
    }

    /**
     * Build section nav keyboard. Listen/content buttons at top (if any),
     * then section nav, then menu. No redundant "selected section" button.
     */
    private function sectionNavKeyboard(DailyContent $daily, Member $member, string $currentSection, string $dailyId): array
    {
        $locale = $this->memberLocale($member);
        $rows = [];

        $sectionsWithContent = $this->sectionsWithContent($daily, $locale);

        $listenButtons = $this->listenButtonsForSection($daily, $locale, $currentSection);
        foreach ($listenButtons as $btn) {
            $rows[] = [$btn];
        }

        $navButtons = [];
        foreach (self::SECTIONS as $code => $name) {
            if (! ($sectionsWithContent[$name] ?? false) || $name === $currentSection) {
                continue;
            }
            $cb = $this->callbackData('today_sec', $code, $dailyId);
            $label = match ($name) {
                'bible' => '📜 '.__('app.telegram_nav_bible'),
                'mezmur' => '🎵 '.__('app.telegram_nav_mezmur'),
                'sinksar' => '📿 '.__('app.telegram_nav_sinksar'),
                'books' => '📚 '.__('app.telegram_nav_books'),
                'reference' => '🔗 '.__('app.telegram_nav_references'),
                'reflection' => '💭 '.__('app.telegram_nav_reflection'),
                default => $name,
            };
            $navButtons[] = ['text' => $label, 'callback_data' => $cb];
        }
        if ($navButtons !== []) {
            foreach (array_chunk($navButtons, 2) as $chunk) {
                $rows[] = $chunk;
            }
        }

        $rows[] = [['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu']];

        return ['inline_keyboard' => $rows];
    }

    /** @return array<string, bool> */
    private function sectionsWithContent(DailyContent $daily, string $locale): array
    {
        return [
            'bible' => (bool) localized($daily, 'bible_reference', $locale),
            'mezmur' => $daily->mezmurs->isNotEmpty(),
            'sinksar' => (bool) localized($daily, 'sinksar_title', $locale),
            'books' => $daily->books && $daily->books->isNotEmpty(),
            'reference' => $daily->references && $daily->references->isNotEmpty(),
            'reflection' => (bool) localized($daily, 'reflection', $locale),
        ];
    }

    /**
     * Listen/View buttons for the current section. YouTube uses Web App (inline);
     * non-YouTube uses url (opens externally).
     *
     * @return list<array{text: string, web_app?: array{url: string}, url?: string}>
     */
    private function listenButtonsForSection(DailyContent $daily, string $locale, string $section): array
    {
        $buttons = [];
        $embedBase = url(route('telegram.embed'));

        if ($section === 'mezmur') {
            foreach ($daily->mezmurs as $m) {
                $url = $m->mediaUrl($locale);
                if (! $url) {
                    continue;
                }
                $vid = $this->youtubeVideoId($url);
                $fullTitle = localized($m, 'title', $locale) ?? __('app.listen');
                $btnTitle = mb_strlen($fullTitle) > 25 ? mb_substr($fullTitle, 0, 22).'…' : $fullTitle;
                if ($vid) {
                    $embedUrl = $embedBase.'?vid='.$vid.'&title='.rawurlencode($fullTitle);
                    $buttons[] = [
                        'text' => '▶ '.$btnTitle,
                        'web_app' => ['url' => $embedUrl],
                    ];
                } else {
                    $buttons[] = ['text' => '▶ '.$btnTitle, 'url' => $this->hUrl($url)];
                }
            }
        }

        if ($section === 'sinksar') {
            $url = $daily->sinksarUrl($locale);
            if ($url) {
                $vid = $this->youtubeVideoId($url);
                if ($vid) {
                    $sinksarTitle = localized($daily, 'sinksar_title', $locale) ?? __('app.sinksar');
                    $embedUrl = $embedBase.'?vid='.$vid.'&title='.rawurlencode($sinksarTitle);
                    $buttons[] = [
                        'text' => '▶ '.__('app.listen'),
                        'web_app' => ['url' => $embedUrl],
                    ];
                } else {
                    $buttons[] = ['text' => '▶ '.__('app.listen'), 'url' => $this->hUrl($url)];
                }
            }
        }

        if ($section === 'reference') {
            foreach ($daily->references ?? [] as $ref) {
                $url = $ref->mediaUrl($locale);
                if (! $url) {
                    continue;
                }
                $name = localized($ref, 'name', $locale) ?? __('app.view_video');
                $name = mb_strlen($name) > 25 ? mb_substr($name, 0, 22).'…' : $name;
                $refType = $ref->type ?? 'website';
                $vid = $this->youtubeVideoId($url);
                if ($vid && $refType === 'video') {
                    $embedUrl = $embedBase.'?vid='.$vid.'&title='.rawurlencode($name);
                    $buttons[] = [
                        'text' => '▶ '.$name,
                        'web_app' => ['url' => $embedUrl],
                    ];
                } else {
                    $label = match ($refType) {
                        'video' => __('app.view_video'),
                        'file' => __('app.view_file'),
                        default => __('app.read_more'),
                    };
                    $buttons[] = ['text' => '▶ '.$name, 'url' => $this->hUrl($url)];
                }
            }
        }

        if ($section === 'books') {
            foreach ($daily->books ?? [] as $book) {
                $url = $book->mediaUrl($locale);
                if (! $url) {
                    continue;
                }
                $vid = $this->youtubeVideoId($url);
                $fullTitle = localized($book, 'title', $locale) ?? __('app.read_more');
                $btnTitle = mb_strlen($fullTitle) > 25 ? mb_substr($fullTitle, 0, 22).'…' : $fullTitle;
                if ($vid) {
                    $embedUrl = $embedBase.'?vid='.$vid.'&title='.rawurlencode($fullTitle);
                    $buttons[] = [
                        'text' => '▶ '.$btnTitle,
                        'web_app' => ['url' => $embedUrl],
                    ];
                } else {
                    $buttons[] = ['text' => __('app.read_more').' →', 'url' => $this->hUrl($url)];
                }
            }
        }

        return $buttons;
    }

    private function youtubeVideoId(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m) === 1) {
            return $m[1];
        }

        return null;
    }

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
                $url = $m->mediaUrl($locale);
                if ($url) {
                    $parts[] = '• '.$title.' <a href="'.$this->hUrl($url).'">▶ '.__('app.listen').'</a>';
                } else {
                    $parts[] = '• '.$title;
                }
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
            $sinksarUrl = $daily->sinksarUrl($locale);
            if ($sinksarUrl) {
                $parts[] = '<a href="'.$this->hUrl($sinksarUrl).'">▶ '.__('app.listen').'</a>';
            }
            $parts[] = '';
            $parts[] = self::DIVIDER;
        }

        if ($daily->books && $daily->books->isNotEmpty()) {
            $parts[] = '<b>📚 '.__('app.spiritual_book').'</b>';
            foreach ($daily->books as $book) {
                $title = $this->h(localized($book, 'title', $locale));
                if ($title === '') {
                    continue;
                }
                $parts[] = $title;
                if (localized($book, 'description', $locale)) {
                    $parts[] = $this->h(localized($book, 'description', $locale));
                }
                $bookUrl = $book->mediaUrl($locale);
                if ($bookUrl) {
                    $parts[] = '<a href="'.$this->hUrl($bookUrl).'">'.__('app.read_more').' →</a>';
                }
            }
            $parts[] = '';
            $parts[] = self::DIVIDER;
        }

        if ($daily->references && $daily->references->isNotEmpty()) {
            $parts[] = '<b>🔗 '.__('app.references').'</b>';
            foreach ($daily->references as $ref) {
                $refUrl = $ref->mediaUrl($locale);
                if (! $refUrl) {
                    continue;
                }
                $name = $this->h(localized($ref, 'name', $locale) ?? '-');
                $refType = $ref->type ?? 'website';
                $label = match ($refType) {
                    'video' => __('app.view_video'),
                    'file' => __('app.view_file'),
                    default => __('app.read_more'),
                };
                $parts[] = '• '.$name.' <a href="'.$this->hUrl($refUrl).'">'.$this->h($label).'</a>';
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

    /** Escape URL for use in HTML href attribute. */
    private function hUrl(?string $url): string
    {
        if ($url === null || $url === '') {
            return '#';
        }

        return str_replace(['&', '"', "'", '<', '>'], ['&amp;', '&quot;', '&#39;', '&lt;', '&gt;'], trim($url));
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

    /**
     * Format Easter countdown and Lent progress for in-chat display.
     *
     * @return array{text: string, use_html: bool}
     */
    public function formatHomeCountdown(CarbonInterface $easterAt, CarbonInterface $lentStartAt): array
    {
        $now = now();
        $diff = (int) max(0, $easterAt->timestamp - $now->timestamp);
        $totalWindow = (int) max(1, $easterAt->timestamp - $lentStartAt->timestamp);
        $elapsed = (int) max(0, $now->timestamp - $lentStartAt->timestamp);
        $progressPct = (int) round(min(100, max(0, ($elapsed / $totalWindow) * 100)));

        $days = (int) floor($diff / 86400);
        $hours = (int) floor(($diff % 86400) / 3600);
        $minutes = (int) floor(($diff % 3600) / 60);
        $seconds = $diff % 60;

        $pad = fn (int $n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
        $bar = $this->progressBar($progressPct);

        $parts = [];
        $parts[] = '<b>⏳ '.__('app.easter_countdown').'</b>';
        $parts[] = '';
        if ($diff > 0) {
            $parts[] = sprintf(
                '<b>%s</b> %s · <b>%s</b> %s · <b>%s</b> %s · <b>%s</b> %s',
                $pad($days),
                __('app.days'),
                $pad($hours),
                __('app.hours'),
                $pad($minutes),
                __('app.minutes'),
                $pad($seconds),
                __('app.seconds')
            );
            $parts[] = __('app.easter_countdown_remaining');
        } else {
            $parts[] = '<b>'.__('app.christ_is_risen').'</b>';
            $parts[] = __('app.easter_countdown_subtitle');
        }
        $parts[] = '';
        $parts[] = __('app.progress').': '.$bar.' '.$progressPct.'%';

        return [
            'text' => implode("\n", $parts),
            'use_html' => true,
        ];
    }
}
