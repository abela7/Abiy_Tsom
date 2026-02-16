<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Services\AbiyTsomStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manage the 55-day daily content feed.
 */
class DailyContentController extends Controller
{
    public function scaffold(): RedirectResponse
    {
        $season = LentSeason::active();
        if (! $season) {
            return redirect('/admin/daily')->with('error', 'No active season.');
        }

        $themes = $season->weeklyThemes()->orderBy('week_number')->get()->keyBy('week_number');
        if ($themes->isEmpty()) {
            return redirect('/admin/daily')->with('error', 'Season has no weeks. Edit the season to regenerate.');
        }

        $created = 0;
        foreach (AbiyTsomStructure::buildDayMetadata($season->start_date) as $meta) {
            if (DailyContent::where('lent_season_id', $season->id)->where('day_number', $meta['day_number'])->exists()) {
                continue;
            }
            $theme = $themes->get(AbiyTsomStructure::getWeekForDay($meta['day_number']));
            if (! $theme) {
                continue;
            }
            DailyContent::create([
                'lent_season_id' => $season->id,
                'weekly_theme_id' => $theme->id,
                'day_number' => $meta['day_number'],
                'date' => $meta['date'],
                'is_published' => false,
            ]);
            $created++;
        }

        return redirect('/admin/daily')->with('success', "Created {$created} day placeholder(s).");
    }

    public function index(): View
    {
        $season = LentSeason::active();
        $contents = $season
            ? $season->dailyContents()->with('weeklyTheme')->orderBy('day_number')->get()
            : collect();

        return view('admin.daily.index', compact('season', 'contents'));
    }

    public function create(): View
    {
        $season = LentSeason::active();
        $themes = $season ? $season->weeklyThemes()->orderBy('week_number')->get() : collect();
        $dayRangesByWeek = $this->getDayRangesByWeek();
        $daily = new DailyContent;

        return view('admin.daily.form', compact('season', 'themes', 'dayRangesByWeek', 'daily'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateContent($request);
        $validated['is_published'] = $request->boolean('is_published');

        $mezmurs = $this->parseMezmurs($request);
        $references = $this->parseReferences($request);
        unset($validated['mezmurs'], $validated['references']);

        $daily = DailyContent::create($validated);
        $this->syncMezmurs($daily, $mezmurs);
        $this->syncReferences($daily, $references);

        return redirect('/admin/daily')->with('success', 'Daily content created.');
    }

    public function edit(DailyContent $daily): View
    {
        $season = LentSeason::active();
        $themes = $season ? $season->weeklyThemes()->orderBy('week_number')->get() : collect();
        $dayRangesByWeek = $this->getDayRangesByWeek();

        return view('admin.daily.form', compact('season', 'themes', 'daily', 'dayRangesByWeek'));
    }

    public function update(Request $request, DailyContent $daily): RedirectResponse
    {
        $validated = $this->validateContent($request, $daily);
        $validated['is_published'] = $request->boolean('is_published');

        $mezmurs = $this->parseMezmurs($request);
        $references = $this->parseReferences($request);
        unset($validated['mezmurs'], $validated['references']);

        $daily->update($validated);
        $this->syncMezmurs($daily, $mezmurs);
        $this->syncReferences($daily, $references);

        return redirect('/admin/daily')->with('success', 'Daily content updated.');
    }

    /**
     * Get day ranges [start, end] per week for client-side week/day resolution.
     *
     * @return array<int, array{0: int, 1: int}>
     */
    private function getDayRangesByWeek(): array
    {
        $ranges = [];
        for ($w = 1; $w <= AbiyTsomStructure::TOTAL_WEEKS; $w++) {
            $ranges[$w] = AbiyTsomStructure::getDayRangeForWeek($w);
        }

        return $ranges;
    }

    /**
     * Validate daily content form data.
     *
     * @return array<string, mixed>
     */
    private function validateContent(Request $request, ?DailyContent $daily = null): array
    {
        $dayUnique = $daily
            ? "unique:daily_contents,day_number,{$daily->id},id,lent_season_id,{$request->input('lent_season_id')}"
            : "unique:daily_contents,day_number,NULL,id,lent_season_id,{$request->input('lent_season_id')}";

        return $request->validate([
            'lent_season_id' => ['required', 'exists:lent_seasons,id'],
            'weekly_theme_id' => ['required', 'exists:weekly_themes,id'],
            'day_number' => ['required', 'integer', 'min:1', 'max:55', $dayUnique],
            'date' => ['required', 'date'],
            'day_title_en' => ['nullable', 'string', 'max:255'],
            'day_title_am' => ['nullable', 'string', 'max:255'],
            'bible_reference_en' => ['nullable', 'string', 'max:255'],
            'bible_reference_am' => ['nullable', 'string', 'max:255'],
            'bible_summary_en' => ['nullable', 'string'],
            'bible_summary_am' => ['nullable', 'string'],
            'bible_text_en' => ['nullable', 'string'],
            'bible_text_am' => ['nullable', 'string'],
            'mezmurs' => ['nullable', 'array'],
            'mezmurs.*.title_en' => ['nullable', 'string', 'max:255'],
            'mezmurs.*.title_am' => ['nullable', 'string', 'max:255'],
            'mezmurs.*.url' => ['nullable', 'url', 'max:500'],
            'mezmurs.*.description_en' => ['nullable', 'string'],
            'mezmurs.*.description_am' => ['nullable', 'string'],
            'sinksar_title_en' => ['nullable', 'string', 'max:255'],
            'sinksar_title_am' => ['nullable', 'string', 'max:255'],
            'sinksar_url' => ['nullable', 'url', 'max:500'],
            'sinksar_description_en' => ['nullable', 'string'],
            'sinksar_description_am' => ['nullable', 'string'],
            'book_title_en' => ['nullable', 'string', 'max:255'],
            'book_title_am' => ['nullable', 'string', 'max:255'],
            'book_url' => ['nullable', 'url', 'max:500'],
            'book_description_en' => ['nullable', 'string'],
            'book_description_am' => ['nullable', 'string'],
            'reflection_en' => ['nullable', 'string'],
            'reflection_am' => ['nullable', 'string'],
            'references' => ['nullable', 'array'],
            'references.*.name_en' => ['nullable', 'string', 'max:255'],
            'references.*.name_am' => ['nullable', 'string', 'max:255'],
            'references.*.url' => ['nullable', 'url', 'max:500'],
        ]);
    }

    /**
     * Parse and filter mezmurs from request (keep only those with title_en or title_am).
     *
     * @return array<int, array{title_en: string|null, title_am: string|null, url: string|null, description_en: string|null, description_am: string|null}>
     */
    private function parseMezmurs(Request $request): array
    {
        $raw = $request->input('mezmurs', []);
        $parsed = [];
        foreach ($raw as $m) {
            $titleEn = trim((string) ($m['title_en'] ?? ''));
            $titleAm = trim((string) ($m['title_am'] ?? ''));
            if ($titleEn === '' && $titleAm === '') {
                continue;
            }
            $parsed[] = [
                'title_en' => $titleEn !== '' ? $titleEn : null,
                'title_am' => $titleAm !== '' ? $titleAm : null,
                'url' => trim((string) ($m['url'] ?? '')) ?: null,
                'description_en' => trim((string) ($m['description_en'] ?? '')) ?: null,
                'description_am' => trim((string) ($m['description_am'] ?? '')) ?: null,
            ];
        }

        return $parsed;
    }

    /**
     * @param  array<int, array{title_en: string|null, title_am: string|null, url: string|null, description_en: string|null, description_am: string|null}>  $mezmurs
     */
    private function syncMezmurs(DailyContent $daily, array $mezmurs): void
    {
        $daily->mezmurs()->delete();
        foreach ($mezmurs as $i => $m) {
            $daily->mezmurs()->create([
                'title_en' => $m['title_en'],
                'title_am' => $m['title_am'],
                'url' => $m['url'],
                'description_en' => $m['description_en'],
                'description_am' => $m['description_am'],
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Parse and filter references from request (keep only those with name_en or name_am and url).
     *
     * @return array<int, array{name_en: string|null, name_am: string|null, url: string}>
     */
    private function parseReferences(Request $request): array
    {
        $raw = $request->input('references', []);
        $parsed = [];
        foreach ($raw as $r) {
            $nameEn = trim((string) ($r['name_en'] ?? ''));
            $nameAm = trim((string) ($r['name_am'] ?? ''));
            $url = trim((string) ($r['url'] ?? ''));
            if (($nameEn !== '' || $nameAm !== '') && $url !== '') {
                $parsed[] = [
                    'name_en' => $nameEn !== '' ? $nameEn : null,
                    'name_am' => $nameAm !== '' ? $nameAm : null,
                    'url' => $url,
                ];
            }
        }

        return $parsed;
    }

    /**
     * @param  array<int, array{name_en: string|null, name_am: string|null, url: string}>  $references
     */
    private function syncReferences(DailyContent $daily, array $references): void
    {
        $daily->references()->delete();
        foreach ($references as $i => $ref) {
            $daily->references()->create([
                'name_en' => $ref['name_en'],
                'name_am' => $ref['name_am'],
                'url' => $ref['url'],
                'sort_order' => $i,
            ]);
        }
    }
}
