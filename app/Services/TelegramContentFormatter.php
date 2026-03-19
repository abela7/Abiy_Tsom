<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\DailyContent;
use App\Models\Lectionary;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Format member content for Telegram. Uses HTML with expandable sections
 * for a modern, sectioned UI. All user content is escaped for safety.
 */
final class TelegramContentFormatter
{
    private const MAX_MESSAGE_LENGTH = 4080;

    /**
     * Budget for Sinksar body inside the Today message (header, title, HTML tags).
     * Telegram sendMessage limit is 4096; we keep a margin below MAX_MESSAGE_LENGTH.
     */
    private const SINKSAR_BODY_QUOTE_MAX = 3200;

    private const DIVIDER = '▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬';

    /** Section codes for callback_data (≤64 bytes). */
    private const SECTIONS = [
        'b' => 'bible',
        'm' => 'mezmur',
        's' => 'sinksar',
        'l' => 'lectionary',
        'k' => 'books',
        'r' => 'reference',
        'f' => 'reflection',
        'c' => 'commemorations',
    ];

    /**
     * Format a single Today section with navigation. For YouTube content,
     * uses Web App buttons so user can watch inline without leaving Telegram.
     *
     * @return array{text: string, use_html: bool, keyboard: array, photos?: list<array{url: string, caption: string}>}
     */
    public function formatDaySection(DailyContent $daily, Member|\App\Models\User $actor, string $section): array
    {
        $locale = $actor instanceof Member ? $this->memberLocale($actor) : app()->getLocale();
        $dailyId = (string) $daily->id;
        $parts = $this->buildSectionHeader($daily, $locale, $section);

        $content = match ($section) {
            'bible' => $this->sectionBible($daily, $locale),
            'mezmur' => $this->sectionMezmur($daily, $locale),
            'sinksar' => $this->sectionSinksar($daily, $locale),
            'lectionary' => $this->sectionLectionary($daily, $locale),
            'books' => $this->sectionBooks($daily, $locale),
            'reference' => $this->sectionReference($daily, $locale),
            'reflection' => $this->sectionReflection($daily, $locale),
            'commemorations' => $this->sectionCommemorations($daily, $locale),
            default => [],
        };

        $parts = array_merge($parts, $content);
        $text = implode("\n", $parts);
        $keyboard = $this->sectionNavKeyboard($daily, $locale, $section, $dailyId);

        $result = [
            'text' => mb_substr($text, 0, self::MAX_MESSAGE_LENGTH),
            'use_html' => true,
            'keyboard' => $keyboard,
        ];

        // Collect photos to send separately (sinksar saints, commemoration images)
        $photos = $this->collectPhotosForSection($daily, $locale, $section);
        if ($photos !== []) {
            $result['photos'] = $photos;
        }

        return $result;
    }

    /**
     * Collect photo URLs to send as separate photo messages.
     *
     * @return list<array{url: string, caption: string}>
     */
    private function collectPhotosForSection(DailyContent $daily, string $locale, string $section): array
    {
        $photos = [];

        if ($section === 'sinksar' && $daily->sinksarImages && $daily->sinksarImages->isNotEmpty()) {
            foreach ($daily->sinksarImages as $img) {
                if (! $img->image_path) {
                    continue;
                }
                $caption = localized($img, 'caption', $locale) ?? '';
                $photos[] = ['url' => $img->imageUrl(), 'caption' => $caption];
            }
        }

        // Commemorations photos are shown via web_app page instead

        return array_slice($photos, 0, 5); // Limit to 5 photos max
    }

    private function buildSectionHeader(DailyContent $daily, string $locale, string $section): array
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
            'lectionary' => __('app.telegram_nav_lectionary'),
            'books' => __('app.telegram_nav_books'),
            'reference' => __('app.telegram_nav_references'),
            'reflection' => __('app.telegram_nav_reflection'),
            'commemorations' => __('app.telegram_nav_commemorations'),
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
        foreach ($daily->mezmurs as $i => $m) {
            $title = $this->h(localized($m, 'title', $locale) ?? '-');
            if ($i > 0) {
                $parts[] = '';
            }
            $parts[] = '<b>🎵 '.$title.'</b>';

            $desc = localized($m, 'description', $locale);
            if ($desc) {
                $parts[] = '<i>'.$this->h($desc).'</i>';
            }

            $hasLyrics = localized($m, 'lyrics', $locale) && trim(localized($m, 'lyrics', $locale)) !== '';
            if ($hasLyrics) {
                $parts[] = ($locale === 'am' ? '📝 ግጥም ከታች ይገኛል' : '📝 Lyrics available in the player');
            }
        }
        $parts[] = '';
        $parts[] = '<i>'.($locale === 'am' ? '▶ ከታች ያለውን ቁልፍ ይጫኑ ለማዳመጥ' : '▶ Tap a button below to play').'</i>';

        return $parts;
    }

    private function sectionSinksar(DailyContent $daily, string $locale): array
    {
        $parts = [];
        if (! localized($daily, 'sinksar_title', $locale)) {
            $parts[] = __('app.no_content');

            return $parts;
        }
        $parts[] = '<b>'.$this->h(localized($daily, 'sinksar_title', $locale)).'</b>';
        $parts[] = '';
        if (localized($daily, 'sinksar_description', $locale)) {
            $parts[] = '<i>'.$this->h(localized($daily, 'sinksar_description', $locale)).'</i>';
            $parts[] = '';
        }

        // Note: saint photos are sent as separate photo messages

        // Full sinksar text (was 1200 chars — too small; use most of Telegram limit)
        $sinksarText = $daily->sinksarText($locale);
        if ($sinksarText) {
            $parts[] = $this->expandableQuote($this->h($sinksarText), self::SINKSAR_BODY_QUOTE_MAX);
        }

        return $parts;
    }

    /**
     * Lectionary overview — shows reading titles only, users tap buttons to read each one.
     */
    private function sectionLectionary(DailyContent $daily, string $locale): array
    {
        $parts = [];
        $lectionary = $this->loadLectionary($daily);

        if (! $lectionary || ! $lectionary->hasContent()) {
            $parts[] = __('app.no_content');

            return $parts;
        }

        $readings = $this->buildLectionaryReadings($lectionary, $locale);

        foreach ($readings as $r) {
            if (! $r['has']) {
                continue;
            }
            $ref = '';
            if ($r['book'] && $r['chapter']) {
                $ref = ' — '.$this->h($r['book']).' '.$r['chapter'];
                if (filled($r['verses'])) {
                    $ref .= ':'.$this->h($r['verses']);
                }
            }
            $parts[] = $r['icon'].' <b>'.$this->h($r['label']).'</b>'.$ref;
        }

        $parts[] = '';
        $parts[] = '<i>'.($locale === 'am' ? 'ከታች ያለውን ቁልፍ ይጫኑ ለማንበብ' : 'Tap a reading below to open').'</i>';

        return $parts;
    }

    /**
     * Format a single lectionary reading for display.
     *
     * @return array{text: string, use_html: bool, keyboard: array}
     */
    public function formatLectionaryReading(DailyContent $daily, Member|\App\Models\User $actor, int $readingNum): array
    {
        $locale = $actor instanceof Member ? $this->memberLocale($actor) : app()->getLocale();
        $dailyId = (string) $daily->id;
        $lectionary = $this->loadLectionary($daily);

        $parts = $this->buildSectionHeader($daily, $locale, 'lectionary');

        if (! $lectionary || ! $lectionary->hasContent()) {
            $parts[] = __('app.no_content');

            return ['text' => implode("\n", $parts), 'use_html' => true, 'keyboard' => []];
        }

        $readings = $this->buildLectionaryReadings($lectionary, $locale);
        $reading = $readings[$readingNum - 1] ?? null;

        if (! $reading || ! $reading['has']) {
            $parts[] = __('app.no_content');

            return ['text' => implode("\n", $parts), 'use_html' => true, 'keyboard' => []];
        }

        // Title
        $parts[] = '<b>'.$reading['icon'].' '.$this->h($reading['label']).'</b>';
        $parts[] = '';

        // Reference
        if ($reading['book'] && $reading['chapter']) {
            $ref = $this->h($reading['book']).' '.$reading['chapter'];
            if (filled($reading['verses'])) {
                $ref .= ':'.$this->h($reading['verses']);
            }
            $parts[] = '<b>'.$ref.'</b>';
            $parts[] = '';
        }

        // Geez verses for Mesbak
        if (! empty($reading['geez'])) {
            foreach ([1, 2, 3] as $n) {
                $geez = $lectionary->{'mesbak_geez_'.$n};
                if (filled($geez)) {
                    $geezNum = ['', '፩', '፪', '፫'][$n] ?? '';
                    $parts[] = '<i>'.$geezNum.' '.$this->h($geez).'</i>';
                }
            }
            $parts[] = '';
        }

        // Full text
        if (filled($reading['text'])) {
            $parts[] = $this->expandableQuote($this->h($reading['text']), 2500);
        }

        // Build keyboard: other readings + nav
        $rows = [];
        foreach ($readings as $r) {
            if (! $r['has'] || (int) $r['num'] === $readingNum) {
                continue;
            }
            $btnLabel = $r['icon'].' '.$r['label'];
            if (mb_strlen($btnLabel) > 28) {
                $btnLabel = mb_substr($btnLabel, 0, 25).'…';
            }
            $rows[] = ['text' => $btnLabel, 'callback_data' => 'lect_'.$r['num'].'_'.$dailyId];
        }
        $keyboard = ['inline_keyboard' => []];
        foreach (array_chunk($rows, 2) as $chunk) {
            $keyboard['inline_keyboard'][] = $chunk;
        }
        // Back to lectionary overview + today + menu
        $keyboard['inline_keyboard'][] = [
            ['text' => '⛪ '.($locale === 'am' ? 'ቅዳሴ ዝርዝር' : 'All Readings'), 'callback_data' => $this->callbackData('today_sec', 'l', $dailyId)],
        ];
        $keyboard['inline_keyboard'][] = [
            ['text' => '📖 '.($locale === 'am' ? 'ዛሬ' : 'Today'), 'callback_data' => 'today'],
            ['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu'],
        ];

        return [
            'text' => mb_substr(implode("\n", $parts), 0, self::MAX_MESSAGE_LENGTH),
            'use_html' => true,
            'keyboard' => $keyboard,
        ];
    }

    private function loadLectionary(DailyContent $daily): ?Lectionary
    {
        if (! $daily->date) {
            return null;
        }
        $ethCal = app(EthiopianCalendarService::class);
        $eth = $ethCal->gregorianToEthiopian($daily->date);

        return Lectionary::where('month', $eth['month'])->where('day', $eth['day'])->first();
    }

    /**
     * Build the 6 lectionary readings array matching the web version labels exactly.
     *
     * @return list<array{label: string, book: ?string, chapter: mixed, verses: mixed, text: ?string, has: bool, icon: string, num: string, geez?: bool}>
     */
    private function buildLectionaryReadings(Lectionary $lectionary, string $locale): array
    {
        $suffix = $locale === 'am' ? '_am' : '_en';

        return [
            [
                'label' => __('app.lectionary_pauline'),
                'book' => $lectionary->{'pauline_book'.$suffix},
                'chapter' => $lectionary->pauline_chapter,
                'verses' => $lectionary->pauline_verses,
                'text' => $lectionary->{'pauline_text'.$suffix},
                'has' => filled($lectionary->pauline_book_am) || filled($lectionary->pauline_chapter),
                'icon' => '📜',
                'num' => '1',
            ],
            [
                'label' => __('app.lectionary_catholic'),
                'book' => $lectionary->{'catholic_book'.$suffix},
                'chapter' => $lectionary->catholic_chapter,
                'verses' => $lectionary->catholic_verses,
                'text' => $lectionary->{'catholic_text'.$suffix},
                'has' => filled($lectionary->catholic_book_am) || filled($lectionary->catholic_chapter),
                'icon' => '📜',
                'num' => '2',
            ],
            [
                'label' => __('app.lectionary_acts'),
                'book' => $locale === 'am' ? 'የሐዋርያት ሥራ' : 'Acts',
                'chapter' => $lectionary->acts_chapter,
                'verses' => $lectionary->acts_verses,
                'text' => $lectionary->{'acts_text'.$suffix},
                'has' => filled($lectionary->acts_chapter),
                'icon' => '📜',
                'num' => '3',
            ],
            [
                'label' => __('app.lectionary_mesbak'),
                'book' => $locale === 'am' ? 'መዝ.' : 'Ps.',
                'chapter' => $lectionary->mesbak_psalm,
                'verses' => $lectionary->mesbak_verses,
                'text' => $lectionary->{'mesbak_text'.$suffix},
                'has' => filled($lectionary->mesbak_psalm),
                'icon' => '🎵',
                'num' => '4',
                'geez' => true,
            ],
            [
                'label' => $locale === 'am'
                    ? (filled($lectionary->gospel_book_am) ? 'የ'.$lectionary->gospel_book_am.' ወንጌል' : __('app.lectionary_gospel'))
                    : (filled($lectionary->gospel_book_en) ? $lectionary->gospel_book_en.' Gospel' : __('app.lectionary_gospel')),
                'book' => $lectionary->{'gospel_book'.$suffix},
                'chapter' => $lectionary->gospel_chapter,
                'verses' => $lectionary->gospel_verses,
                'text' => $lectionary->{'gospel_text'.$suffix},
                'has' => filled($lectionary->gospel_book_am) || filled($lectionary->gospel_chapter),
                'icon' => '✝️',
                'num' => '5',
            ],
            [
                'label' => $locale === 'am'
                    ? (filled($lectionary->qiddase_am) ? $lectionary->qiddase_am : __('app.lectionary_qiddase'))
                    : (filled($lectionary->qiddase_en) ? $lectionary->qiddase_en : __('app.lectionary_qiddase')),
                'book' => null,
                'chapter' => null,
                'verses' => null,
                'text' => $lectionary->{'qiddase'.$suffix},
                'has' => filled($lectionary->qiddase_am) || filled($lectionary->qiddase_en),
                'icon' => '⛪',
                'num' => '6',
            ],
        ];
    }

    private function sectionCommemorations(DailyContent $daily, string $locale): array
    {
        $parts = [];

        if (! $daily->date) {
            $parts[] = __('app.no_content');

            return $parts;
        }

        $ethCal = app(EthiopianCalendarService::class);
        $dateInfo = $ethCal->getDateInfo($daily->date, $locale);

        $parts[] = '<b>'.$this->h($dateInfo['ethiopian_date_formatted']).'</b>';
        $parts[] = '';

        $annuals = $dateInfo['annual_celebrations'];
        $monthlies = $dateInfo['monthly_celebrations'];

        if ($annuals->isNotEmpty()) {
            $parts[] = '<b>'.($locale === 'am' ? 'ዓመታዊ በዓላት' : 'Annual Celebrations').'</b>';
            $parts[] = '';
            foreach ($annuals as $c) {
                $name = $this->h(localized($c, 'celebration', $locale) ?? '-');
                $icon = $c->is_main ? '⭐' : '•';
                $parts[] = $icon.' <b>'.$name.'</b>';
            }
            $parts[] = '';
        }

        if ($monthlies->isNotEmpty()) {
            $parts[] = '<b>'.($locale === 'am' ? 'ወርሃዊ በዓላት' : 'Monthly Commemorations').'</b>';
            $parts[] = '';
            foreach ($monthlies as $c) {
                $name = $this->h(localized($c, 'celebration', $locale) ?? '-');
                $icon = $c->is_main ? '⭐' : '•';
                $parts[] = $icon.' <b>'.$name.'</b>';
            }
            $parts[] = '';
        }

        if ($annuals->isEmpty() && $monthlies->isEmpty()) {
            $parts[] = __('app.no_content');
        } else {
            $parts[] = '<i>'.($locale === 'am' ? 'ከታች ያለውን ቁልፍ ይጫኑ ለተጨማሪ' : 'Tap below to view with photos &amp; details').'</i>';
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
        foreach ($daily->books as $i => $book) {
            $title = $this->h(localized($book, 'title', $locale));
            if ($title === '') {
                continue;
            }
            if ($i > 0) {
                $parts[] = '';
            }
            $parts[] = '<b>• '.$title.'</b>';
            $desc = localized($book, 'description', $locale);
            if ($desc) {
                $parts[] = $this->expandableQuote($this->h($desc), 600);
            }
            // Buttons handle the actual read/view action
        }
        $parts[] = '';
        $parts[] = '<i>'.($locale === 'am' ? '📖 ለማንበብ ከታች ያለውን ቁልፍ ይጫኑ' : '📖 Tap the button below to read').'</i>';

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
            $type = $ref->type ?? 'website';
            $icon = match ($type) {
                'video' => '▶',
                'file' => '📄',
                default => '🔗',
            };
            $parts[] = $icon.' <b>'.$name.'</b>';
            $desc = localized($ref, 'description', $locale);
            if ($desc) {
                $parts[] = '<i>'.$this->h(mb_substr($desc, 0, 150)).'</i>';
            }
        }
        $parts[] = '';
        $parts[] = '<i>'.($locale === 'am' ? 'ለመመልከት ከታች ያለውን ቁልፍ ይጫኑ' : 'Tap the buttons below to view').'</i>';

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
    private function sectionNavKeyboard(DailyContent $daily, string $locale, string $currentSection, string $dailyId): array
    {
        $rows = [];

        $sectionsWithContent = $this->sectionsWithContent($daily, $locale);

        $listenButtons = $this->listenButtonsForSection($daily, $locale, $currentSection);

        // Sections with their own action buttons — show ONLY those + back/menu, no section nav
        if ($currentSection === 'lectionary' || $currentSection === 'mezmur') {
            foreach ($listenButtons as $btn) {
                $rows[] = [$btn];
            }
            // Back to overview + Menu
            $rows[] = [
                ['text' => '📖 '.($locale === 'am' ? 'ዛሬ' : 'Today'), 'callback_data' => 'today'],
                ['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu'],
            ];

            return ['inline_keyboard' => $rows];
        }

        // Sinksar: Listen (if any) + Go back only — no section nav, no menu (photos cleared on back)
        if ($currentSection === 'sinksar') {
            foreach ($listenButtons as $btn) {
                $rows[] = [$btn];
            }
            $rows[] = [
                ['text' => '◀️ '.__('app.telegram_sinksar_go_back'), 'callback_data' => 'today'],
            ];

            return ['inline_keyboard' => $rows];
        }

        // For other sections: listen buttons on top, then section nav
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
                'sinksar' => '📖 '.__('app.telegram_nav_sinksar'),
                'lectionary' => '⛪ '.__('app.telegram_nav_lectionary'),
                'books' => '📚 '.__('app.telegram_nav_books'),
                'reference' => '🔗 '.__('app.telegram_nav_references'),
                'reflection' => '💭 '.__('app.telegram_nav_reflection'),
                'commemorations' => __('app.telegram_nav_commemorations'),
                default => $name,
            };
            $navButtons[] = ['text' => $label, 'callback_data' => $cb];
        }
        foreach ($navButtons as $btn) {
            $rows[] = [$btn];
        }

        // Back to overview + Menu
        $rows[] = [
            ['text' => '📖 '.($locale === 'am' ? 'ዛሬ' : 'Today'), 'callback_data' => 'today'],
            ['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu'],
        ];

        return ['inline_keyboard' => $rows];
    }

    /** @return array<string, bool> */
    private function sectionsWithContent(DailyContent $daily, string $locale): array
    {
        $hasLectionary = false;
        $hasCommemorations = false;

        if ($daily->date) {
            $ethCal = app(EthiopianCalendarService::class);
            $eth = $ethCal->gregorianToEthiopian($daily->date);
            $lectionary = Lectionary::where('month', $eth['month'])->where('day', $eth['day'])->first();
            $hasLectionary = $lectionary && $lectionary->hasContent();
            $celebrations = $ethCal->getCelebrationsForDate($daily->date);
            $hasCommemorations = $celebrations->isNotEmpty();
        }

        return [
            'bible' => (bool) localized($daily, 'bible_reference', $locale),
            'mezmur' => $daily->mezmurs->isNotEmpty(),
            'sinksar' => (bool) localized($daily, 'sinksar_title', $locale),
            'lectionary' => $hasLectionary,
            'books' => $daily->books && $daily->books->isNotEmpty(),
            'reference' => $daily->references && $daily->references->isNotEmpty(),
            'reflection' => (bool) localized($daily, 'reflection', $locale),
            'commemorations' => $hasCommemorations,
        ];
    }

    /**
     * Listen/View buttons for the current section. Everything opens in web_app
     * (YouTube embed, audio player, or mezmur player) — no raw URL links.
     *
     * @return list<array{text: string, web_app?: array{url: string}, url?: string}>
     */
    private function listenButtonsForSection(DailyContent $daily, string $locale, string $section): array
    {
        $buttons = [];
        $dailyId = (string) $daily->id;
        $embedBase = url(route('telegram.embed'));
        $audioBase = url(route('telegram.audio'));
        $mezmurBase = url(route('telegram.mezmur'));

        if ($section === 'mezmur') {
            foreach ($daily->mezmurs as $m) {
                $url = $m->mediaUrl($locale);
                if (! $url && ! localized($m, 'lyrics', $locale)) {
                    continue;
                }
                $fullTitle = localized($m, 'title', $locale) ?? __('app.listen');
                $btnTitle = mb_strlen($fullTitle) > 25 ? mb_substr($fullTitle, 0, 22).'…' : $fullTitle;
                // Always open mezmur player (shows video/audio + lyrics)
                $playerUrl = $mezmurBase.'?id='.$m->id.'&lang='.$locale;
                $buttons[] = [
                    'text' => '▶ '.$btnTitle,
                    'web_app' => ['url' => $playerUrl],
                ];
            }
        }

        if ($section === 'sinksar') {
            $url = $daily->sinksarUrl($locale);
            if ($url) {
                $buttons[] = $this->mediaButton(
                    '▶ '.__('app.listen'),
                    $url,
                    localized($daily, 'sinksar_title', $locale) ?? __('app.sinksar'),
                    $embedBase,
                    $audioBase
                );
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
                if ($refType === 'video') {
                    $buttons[] = $this->mediaButton('▶ '.$name, $url, $name, $embedBase, $audioBase);
                } else {
                    // Files and websites open in web_app for inline viewing
                    $buttons[] = ['text' => '📄 '.$name, 'web_app' => ['url' => $url]];
                }
            }
        }

        // Bible audio — always a player, never a raw link
        if ($section === 'bible') {
            $bibleAudio = $daily->bibleAudioUrl($locale);
            if ($bibleAudio) {
                $buttons[] = $this->mediaButton(
                    '▶ '.($locale === 'am' ? 'ያዳምጡ' : 'Listen'),
                    $bibleAudio,
                    localized($daily, 'bible_reference', $locale) ?? __('app.telegram_nav_bible'),
                    $embedBase,
                    $audioBase
                );
            }
        }

        if ($section === 'books') {
            foreach ($daily->books ?? [] as $book) {
                $url = $book->mediaUrl($locale);
                if (! $url) {
                    continue;
                }
                $fullTitle = localized($book, 'title', $locale) ?? __('app.read_more');
                $btnTitle = mb_strlen($fullTitle) > 25 ? mb_substr($fullTitle, 0, 22).'…' : $fullTitle;
                $vid = $this->youtubeVideoId($url);
                if ($vid) {
                    $embedUrl = $embedBase.'?vid='.$vid.'&title='.rawurlencode($fullTitle);
                    $buttons[] = ['text' => '📖 '.$btnTitle, 'web_app' => ['url' => $embedUrl]];
                } else {
                    // Open PDF/book in web_app for inline viewing
                    $buttons[] = ['text' => '📖 '.$btnTitle, 'web_app' => ['url' => $url]];
                }
            }
        }

        // Lectionary — individual reading buttons
        if ($section === 'lectionary') {
            $lectionary = $this->loadLectionary($daily);
            if ($lectionary && $lectionary->hasContent()) {
                $readings = $this->buildLectionaryReadings($lectionary, $locale);
                foreach ($readings as $r) {
                    if (! $r['has']) {
                        continue;
                    }
                    $btnLabel = $r['icon'].' '.$r['label'];
                    if (mb_strlen($btnLabel) > 28) {
                        $btnLabel = mb_substr($btnLabel, 0, 25).'…';
                    }
                    $buttons[] = ['text' => $btnLabel, 'callback_data' => 'lect_'.$r['num'].'_'.$dailyId];
                }
            }
        }

        // Commemorations — open the public Telegram commemorations page
        if ($section === 'commemorations' && $daily->date) {
            $commUrl = url(route('telegram.commemorations', ['daily' => $daily->id, 'lang' => $locale]));
            $buttons[] = [
                'text' => '🖼 '.($locale === 'am' ? 'ፎቶ እና ዝርዝር ይመልከቱ' : 'View Photos & Details'),
                'web_app' => ['url' => $commUrl],
            ];
        }

        return $buttons;
    }

    /**
     * Build a web_app button for any media URL (YouTube → embed, other → audio player).
     */
    private function mediaButton(string $label, string $url, string $title, string $embedBase, string $audioBase): array
    {
        $vid = $this->youtubeVideoId($url);
        if ($vid) {
            $embedUrl = $embedBase.'?vid='.$vid.'&title='.rawurlencode($title);

            return ['text' => $label, 'web_app' => ['url' => $embedUrl]];
        }

        $audioUrl = $audioBase.'?url='.rawurlencode($url).'&title='.rawurlencode($title);

        return ['text' => $label, 'web_app' => ['url' => $audioUrl]];
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
     * Format a day overview with section navigation keyboard.
     * Shows a clean summary without links — users tap section buttons to read.
     *
     * @return array{text: string, use_html: bool, keyboard: array}
     */
    public function formatDayOverview(DailyContent $daily, Member|\App\Models\User $actor): array
    {
        $locale = $actor instanceof Member ? $this->memberLocale($actor) : app()->getLocale();
        $dailyId = (string) $daily->id;
        $parts = [];

        $dayTitle = $this->h(localized($daily, 'day_title', $locale) ?? __('app.day_x', ['day' => $daily->day_number]));
        $dateStr = $daily->date?->locale('en')->translatedFormat('l, F j, Y') ?? '';

        $parts[] = '<b>📖 Day '.$daily->day_number.' of 55</b>';
        $parts[] = '<i>'.$dateStr.'</i>';
        if ($daily->weeklyTheme) {
            $themeName = $this->h(localized($daily->weeklyTheme, 'name', $locale) ?? $daily->weeklyTheme->name_en ?? '-');
            $parts[] = '<i>'.$themeName.'</i>';
        }

        // Ethiopian date
        if ($daily->date) {
            $ethCal = app(EthiopianCalendarService::class);
            $ethFormatted = $ethCal->formatEthiopianDate($daily->date, $locale);
            $parts[] = '<i>'.$this->h($ethFormatted).'</i>';
        }

        $parts[] = '';
        $parts[] = '<b>'.$dayTitle.'</b>';
        $parts[] = '';
        $parts[] = self::DIVIDER;
        $parts[] = '';

        // Section summaries — just headlines, no links
        $sectionsWithContent = $this->sectionsWithContent($daily, $locale);

        if ($sectionsWithContent['bible']) {
            $ref = localized($daily, 'bible_reference', $locale);
            $parts[] = '📜 <b>'.__('app.telegram_nav_bible').'</b>';
            if ($ref) {
                $parts[] = '   '.$this->h($ref);
            }
            $parts[] = '';
        }

        if ($sectionsWithContent['mezmur']) {
            $parts[] = '🎵 <b>'.__('app.telegram_nav_mezmur').'</b>';
            foreach ($daily->mezmurs->take(3) as $m) {
                $parts[] = '   • '.$this->h(localized($m, 'title', $locale) ?? '-');
            }
            $parts[] = '';
        }

        if ($sectionsWithContent['sinksar']) {
            $parts[] = '📖 <b>'.__('app.telegram_nav_sinksar').'</b>';
            $parts[] = '   '.$this->h(localized($daily, 'sinksar_title', $locale));
            $parts[] = '';
        }

        if ($sectionsWithContent['lectionary']) {
            $parts[] = '⛪ <b>'.__('app.telegram_nav_lectionary').'</b>';
            $parts[] = '   '.($locale === 'am' ? 'ጳውሎስ · ካቶሊኮን · ግብረ ሐዋርያት · መዝሙር · ወንጌል · ቅዳሴ' : 'Pauline · Catholic · Acts · Psalm · Gospel · Qiddase');
            $parts[] = '';
        }

        if ($sectionsWithContent['books']) {
            $parts[] = '📚 <b>'.__('app.telegram_nav_books').'</b>';
            foreach ($daily->books->take(2) as $book) {
                $title = localized($book, 'title', $locale);
                if ($title) {
                    $parts[] = '   • '.$this->h($title);
                }
            }
            $parts[] = '';
        }

        if ($sectionsWithContent['reference']) {
            $parts[] = '🔗 <b>'.__('app.telegram_nav_references').'</b>';
            $parts[] = '';
        }

        if ($sectionsWithContent['reflection']) {
            $parts[] = '💭 <b>'.__('app.telegram_nav_reflection').'</b>';
            $parts[] = '';
        }

        if ($sectionsWithContent['commemorations']) {
            $ethCal = app(EthiopianCalendarService::class);
            $celebrations = $ethCal->getCelebrationsForDate($daily->date);
            $parts[] = '🎉 <b>'.__('app.telegram_nav_commemorations').'</b>';
            foreach ($celebrations->take(3) as $c) {
                $parts[] = '   • '.$this->h(localized($c, 'celebration', $locale) ?? '-');
            }
            if ($celebrations->count() > 3) {
                $parts[] = '   <i>+'.$this->h((string) ($celebrations->count() - 3)).' '.($locale === 'am' ? 'ተጨማሪ' : 'more').'</i>';
            }
            $parts[] = '';
        }

        $parts[] = self::DIVIDER;
        $parts[] = $locale === 'am'
            ? '<i>ከታች ያሉትን ክፍሎች በመጫን ያንብቡ ▼</i>'
            : '<i>Tap a section below to read ▼</i>';

        // Build keyboard with all available sections
        $navButtons = [];
        foreach (self::SECTIONS as $code => $name) {
            if (! ($sectionsWithContent[$name] ?? false)) {
                continue;
            }
            $cb = $this->callbackData('today_sec', $code, $dailyId);
            $label = match ($name) {
                'bible' => '📜 '.__('app.telegram_nav_bible'),
                'mezmur' => '🎵 '.__('app.telegram_nav_mezmur'),
                'sinksar' => '📖 '.__('app.telegram_nav_sinksar'),
                'lectionary' => '⛪ '.__('app.telegram_nav_lectionary'),
                'books' => '📚 '.__('app.telegram_nav_books'),
                'reference' => '🔗 '.__('app.telegram_nav_references'),
                'reflection' => '💭 '.__('app.telegram_nav_reflection'),
                'commemorations' => __('app.telegram_nav_commemorations'),
                default => $name,
            };
            $navButtons[] = ['text' => $label, 'callback_data' => $cb];
        }

        $rows = [];
        foreach ($navButtons as $btn) {
            $rows[] = [$btn];
        }

        $rows[] = [['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu']];

        $text = implode("\n", $parts);

        return [
            'text' => mb_substr($text, 0, self::MAX_MESSAGE_LENGTH),
            'use_html' => true,
            'keyboard' => ['inline_keyboard' => $rows],
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

    /**
     * Format progress report for a given period (daily, weekly, monthly, all).
     *
     * Mirrors ProgressController::data() logic exactly — no caching of
     * Eloquent models to avoid broken date casts after deserialization.
     *
     * @return array{text: string, use_html: bool, keyboard: array}
     */
    public function formatProgressForPeriod(Member $member, string $period = 'all'): array
    {
        $season = LentSeason::active();
        if (! $season) {
            return [
                'text' => __('app.no_active_season'),
                'use_html' => false,
                'keyboard' => [],
            ];
        }

        $allDays = DailyContent::where('lent_season_id', $season->id)
            ->where('is_published', true)
            ->orderBy('day_number')
            ->get();

        $today = Carbon::today();
        $referenceDay = $this->getProgressReferenceDay($allDays, $today);
        $periodDays = $this->filterProgressByPeriod($allDays, $period, $referenceDay);

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

        $periodDayIds = $periodDays->pluck('id');
        $checks = $allChecks->whereIn('daily_content_id', $periodDayIds);
        $customChecks = $allCustomChecks->whereIn('daily_content_id', $periodDayIds);

        $periodDayCount = $periodDays->count();
        $totalChecks = $checks->count() + $customChecks->count();
        $overall = ($periodDayCount > 0 && $totalActivities > 0)
            ? (int) round(($totalChecks / ($periodDayCount * $totalActivities)) * 100)
            : 0;

        $streak = $this->computeProgressStreak($allDays, $allChecks, $allCustomChecks, $today);

        $locale = $this->memberLocale($member);
        $periodLabel = match ($period) {
            'daily' => __('app.period_daily'),
            'weekly' => __('app.period_weekly'),
            'monthly' => __('app.period_monthly'),
            default => __('app.period_all'),
        };

        $parts = [];
        $parts[] = '<b>📊 '.__('app.progress').' — '.$periodLabel.'</b>';
        $parts[] = '<i>'.now()->format('H:i:s').' · '.$periodDayCount.' '.($periodDayCount === 1 ? 'day' : 'days').'</i>';
        $parts[] = '';
        $parts[] = __('app.overall_completion', ['pct' => $overall]);
        $parts[] = __('app.streak_days', ['count' => $streak]);
        $parts[] = '';

        $allActivities = $activities->merge($customActivities);
        foreach ($allActivities as $a) {
            $isCustom = $a instanceof \App\Models\MemberCustomActivity;
            $done = $isCustom
                ? $customChecks->where('member_custom_activity_id', $a->id)->count()
                : $checks->where('activity_id', $a->id)->count();
            $rate = $periodDayCount > 0 ? (int) round(($done / $periodDayCount) * 100) : 0;
            $rawName = $isCustom
                ? ($a->name ?? '-')
                : (localized($a, 'name', $locale) ?? $a->name ?? '-');
            $name = $this->escapeHtml($this->safeText($rawName));
            $bar = $this->progressBar($rate);
            $parts[] = "{$name}: {$bar} {$rate}%";
        }

        $keyboard = $this->progressPeriodKeyboard($period);
        $keyboard['inline_keyboard'][] = [['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu']];

        return [
            'text' => implode("\n", $parts),
            'use_html' => true,
            'keyboard' => $keyboard,
        ];
    }

    /**
     * Best available reference day for period defaults.
     * Mirrors ProgressController::getReferenceDay() exactly.
     *
     * @param  Collection<int, DailyContent>  $allDays
     */
    private function getProgressReferenceDay(Collection $allDays, Carbon $anchorDate): ?DailyContent
    {
        if ($allDays->isEmpty()) {
            return null;
        }

        $todayContent = $allDays->first(
            fn (DailyContent $d) => $d->date && $d->date->isSameDay($anchorDate)
        );
        if ($todayContent) {
            return $todayContent;
        }

        $previous = $allDays
            ->filter(fn (DailyContent $d) => $d->date && $d->date->isBefore($anchorDate))
            ->sortByDesc('date')
            ->first();

        if ($previous) {
            return $previous;
        }

        return $allDays
            ->filter(fn (DailyContent $d) => $d->date && $d->date->isAfter($anchorDate))
            ->sortBy('date')
            ->first();
    }

    /**
     * Filter days by the requested period.
     * Mirrors ProgressController::filterByPeriod() exactly.
     *
     * @param  Collection<int, DailyContent>  $allDays
     */
    private function filterProgressByPeriod(
        Collection $allDays,
        string $period,
        ?DailyContent $referenceDay
    ): Collection {
        return match ($period) {
            'daily' => $this->filterProgressDaily($allDays, $referenceDay),
            'weekly' => $this->filterProgressWeekly($allDays, $referenceDay),
            'monthly' => $this->filterProgressMonthly($allDays, $referenceDay),
            default => $allDays,
        };
    }

    /** @param  Collection<int, DailyContent>  $allDays */
    private function filterProgressDaily(Collection $allDays, ?DailyContent $referenceDay): Collection
    {
        if (! $referenceDay) {
            return $allDays->take(0);
        }

        return $allDays->filter(
            fn (DailyContent $d) => $d->day_number === $referenceDay->day_number
        )->values();
    }

    /**
     * Filter days to the canonical week that contains the reference day.
     *
     * Telegram has no week picker, so we always use the hardcoded
     * AbiyTsomStructure ranges as the primary source of truth —
     * this is reliable regardless of whether weekly_theme_id is
     * assigned in the database.  The theme-ID approach is kept
     * only as a last-resort fallback.
     *
     * @param  Collection<int, DailyContent>  $allDays
     */
    private function filterProgressWeekly(Collection $allDays, ?DailyContent $referenceDay): Collection
    {
        if (! $referenceDay) {
            if ($allDays->isEmpty()) {
                return collect();
            }

            $firstDay = (int) $allDays->min('day_number');
            $lastDay = (int) $allDays->max('day_number');

            return $allDays->filter(
                fn (DailyContent $d) => $d->day_number >= $firstDay
                    && $d->day_number <= min($firstDay + 6, $lastDay)
            )->values();
        }

        // Primary: canonical week range from AbiyTsomStructure
        $weekNum = AbiyTsomStructure::getWeekForDay((int) $referenceDay->day_number);
        [$start, $end] = AbiyTsomStructure::getDayRangeForWeek($weekNum);

        $byRange = $allDays->filter(
            fn (DailyContent $d) => $d->day_number >= $start && $d->day_number <= $end
        )->values();

        if ($byRange->isNotEmpty()) {
            return $byRange;
        }

        // Fallback: weekly_theme_id (in case day_number is out of range)
        if ($referenceDay->weekly_theme_id) {
            $byTheme = $allDays
                ->where('weekly_theme_id', $referenceDay->weekly_theme_id)
                ->values();
            if ($byTheme->isNotEmpty()) {
                return $byTheme;
            }
        }

        return collect();
    }

    /**
     * Get days for the month of the reference day.
     * Mirrors ProgressController::getMonthlyDays() exactly.
     *
     * @param  Collection<int, DailyContent>  $allDays
     */
    private function filterProgressMonthly(Collection $allDays, ?DailyContent $referenceDay): Collection
    {
        if ($allDays->isEmpty()) {
            return collect();
        }

        $monthDate = $referenceDay?->date ?? $allDays->sortBy('date')->first()?->date;
        if (! $monthDate) {
            return collect();
        }

        $monthItems = $allDays->filter(
            fn (DailyContent $d) => $d->date && $d->date->isSameMonth($monthDate)
        )->values();

        return $monthItems->isNotEmpty() ? $monthItems : $allDays->take(0);
    }

    /**
     * Consecutive days with >= 1 completion, walking backwards from today.
     * Mirrors ProgressController::computeStreak() exactly.
     *
     * @param  Collection<int, DailyContent>  $allDays
     */
    private function computeProgressStreak(
        Collection $allDays,
        Collection $checks,
        Collection $customChecks,
        Carbon $today
    ): int {
        $pastDays = $allDays
            ->filter(fn (DailyContent $d) => $d->date && $d->date->lte($today))
            ->sortByDesc('day_number')
            ->values();

        $streak = 0;
        foreach ($pastDays as $day) {
            $done = $checks->where('daily_content_id', $day->id)->count()
                + $customChecks->where('daily_content_id', $day->id)->count();
            if ($done > 0) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Escape special HTML characters for Telegram HTML parse mode.
     */
    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function progressPeriodKeyboard(string $currentPeriod): array
    {
        $periods = [
            ['key' => 'daily', 'label' => __('app.period_daily')],
            ['key' => 'weekly', 'label' => __('app.period_weekly')],
            ['key' => 'monthly', 'label' => __('app.period_monthly')],
            ['key' => 'all', 'label' => __('app.period_all')],
        ];

        $row = [];
        foreach ($periods as $p) {
            $row[] = [
                'text' => ($p['key'] === $currentPeriod ? '▸ ' : '').$p['label'],
                'callback_data' => 'progress_'.$p['key'],
            ];
        }

        return ['inline_keyboard' => array_chunk($row, 2)];
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
