<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\DailyContentBook;
use App\Models\LentSeason;
use App\Services\AbiyTsomStructure;
use Illuminate\Http\JsonResponse;
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
                'created_by_id' => auth()->id(),
                'updated_by_id' => auth()->id(),
            ]);
            $created++;
        }

        return redirect('/admin/daily')->with('success', "Created {$created} day placeholder(s).");
    }

    public function index(): View
    {
        $season = LentSeason::active();
        $contents = $season
            ? $season->dailyContents()->with(['weeklyTheme', 'createdBy', 'updatedBy'])->orderBy('day_number')->get()
            : collect();

        return view('admin.daily.index', compact('season', 'contents'));
    }

    public function create(): View
    {
        $season = LentSeason::active();
        $themes = $season ? $season->weeklyThemes()->orderBy('week_number')->get() : collect();
        $dayRangesByWeek = $this->getDayRangesByWeek();
        $daily = new DailyContent;
        $initialStep = max(1, min(7, $this->normalizeStep(request())));
        $recentBooks = $this->getRecentBooks(null);
        $daysWithContent = $this->getDaysWithContent(null);

        return view('admin.daily.form', compact('season', 'themes', 'dayRangesByWeek', 'daily', 'initialStep', 'recentBooks', 'daysWithContent'));
    }

    /**
     * Store step-1 data and create a draft daily record.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'lent_season_id' => ['required', 'exists:lent_seasons,id'],
            'weekly_theme_id' => ['required', 'exists:weekly_themes,id'],
            'day_number' => [
                'required',
                'integer',
                'min:1',
                'max:55',
                "unique:daily_contents,day_number,NULL,id,lent_season_id,{$request->input('lent_season_id')}",
            ],
            'date' => ['required', 'date'],
            'day_title_en' => ['nullable', 'string', 'max:255'],
            'day_title_am' => ['nullable', 'string', 'max:255'],
        ]);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['created_by_id'] = auth()->id();
        $validated['updated_by_id'] = auth()->id();

        $daily = DailyContent::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Step saved.',
                'daily_id' => $daily->id,
                'next_step' => 2,
                'edit_url' => route('admin.daily.edit', ['daily' => $daily, 'step' => 2]),
            ]);
        }

        return redirect()
            ->route('admin.daily.edit', ['daily' => $daily, 'step' => 2])
            ->with('success', 'Step saved. Continue with the next section.');
    }

    /**
     * Return full day content for copying into another day.
     * Used when "Copy from day" is clicked in the create/edit form.
     */
    public function copyFrom(int $day_number): JsonResponse
    {
        $season = LentSeason::active();
        if (! $season) {
            return response()->json(['success' => false, 'message' => 'No active season.'], 404);
        }

        $source = DailyContent::where('lent_season_id', $season->id)
            ->where('day_number', $day_number)
            ->with(['mezmurs', 'books', 'references'])
            ->first();

        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Day not found.'], 404);
        }

        $mezmurs = $source->mezmurs->map(fn ($m) => [
            'title_en' => $m->title_en ?? '',
            'title_am' => $m->title_am ?? '',
            'url' => $m->url ?? '',
            'description_en' => $m->description_en ?? '',
            'description_am' => $m->description_am ?? '',
        ])->values()->toArray();

        $references = $source->references->map(fn ($r) => [
            'name_en' => $r->name_en ?? '',
            'name_am' => $r->name_am ?? '',
            'url' => $r->url ?? '',
            'type' => $r->type ?? 'website',
        ])->values()->toArray();

        $books = $source->books->map(fn ($b) => [
            'title_en' => $b->title_en ?? '',
            'title_am' => $b->title_am ?? '',
            'url' => $b->url ?? '',
            'description_en' => $b->description_en ?? '',
            'description_am' => $b->description_am ?? '',
        ])->values()->toArray();

        if (empty($mezmurs)) {
            $mezmurs = [['title_en' => '', 'title_am' => '', 'url' => '', 'description_en' => '', 'description_am' => '']];
        }
        if (empty($references)) {
            $references = [['name_en' => '', 'name_am' => '', 'url' => '', 'type' => 'website']];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'day_title_en' => $source->day_title_en ?? '',
                'day_title_am' => $source->day_title_am ?? '',
                'bible_reference_en' => $source->bible_reference_en ?? '',
                'bible_reference_am' => $source->bible_reference_am ?? '',
                'bible_summary_en' => $source->bible_summary_en ?? '',
                'bible_summary_am' => $source->bible_summary_am ?? '',
                'bible_text_en' => $source->bible_text_en ?? '',
                'bible_text_am' => $source->bible_text_am ?? '',
                'sinksar_title_en' => $source->sinksar_title_en ?? '',
                'sinksar_title_am' => $source->sinksar_title_am ?? '',
                'sinksar_url' => $source->sinksar_url ?? '',
                'sinksar_description_en' => $source->sinksar_description_en ?? '',
                'sinksar_description_am' => $source->sinksar_description_am ?? '',
                'reflection_en' => $source->reflection_en ?? '',
                'reflection_am' => $source->reflection_am ?? '',
                'mezmurs' => $mezmurs,
                'references' => $references,
                'books' => $books,
            ],
        ]);
    }

    public function edit(DailyContent $daily): View
    {
        $daily->load('books');
        $season = LentSeason::active();
        $themes = $season ? $season->weeklyThemes()->orderBy('week_number')->get() : collect();
        $dayRangesByWeek = $this->getDayRangesByWeek();
        $initialStep = max(1, min(7, $this->normalizeStep(request())));
        $recentBooks = $this->getRecentBooks($daily->id);
        $daysWithContent = $this->getDaysWithContent($daily->id);

        return view('admin.daily.form', compact('season', 'themes', 'daily', 'dayRangesByWeek', 'initialStep', 'recentBooks', 'daysWithContent'));
    }

    public function update(Request $request, DailyContent $daily): RedirectResponse
    {
        $validated = $this->validateContent($request, $daily);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['updated_by_id'] = auth()->id();

        $mezmurs = $this->parseMezmurs($request);
        $references = $this->parseReferences($request);
        $books = $this->parseBooks($request);
        unset($validated['mezmurs'], $validated['references'], $validated['books']);

        $daily->update($validated);
        $this->syncMezmurs($daily, $mezmurs);
        $this->syncReferences($daily, $references);
        $this->syncBooks($daily, $books);

        return redirect('/admin/daily')->with('success', 'Daily content updated.');
    }

    /**
     * Save one wizard step at a time.
     */
    public function patch(Request $request, DailyContent $daily): JsonResponse
    {
        $step = $this->normalizeStep($request);
        $updates = [];

        switch ($step) {
            case 1:
                $updates = $request->validate([
                    'lent_season_id' => ['required', 'exists:lent_seasons,id'],
                    'weekly_theme_id' => ['required', 'exists:weekly_themes,id'],
                    'day_number' => [
                        'required',
                        'integer',
                        'min:1',
                        'max:55',
                        "unique:daily_contents,day_number,{$daily->id},id,lent_season_id,{$request->input('lent_season_id', $daily->lent_season_id)}",
                    ],
                    'date' => ['required', 'date'],
                    'day_title_en' => ['nullable', 'string', 'max:255'],
                    'day_title_am' => ['nullable', 'string', 'max:255'],
                ]);
                break;

            case 2:
                $updates = $request->validate([
                    'bible_reference_en' => ['nullable', 'string', 'max:255'],
                    'bible_reference_am' => ['nullable', 'string', 'max:255'],
                    'bible_summary_en' => ['nullable', 'string'],
                    'bible_summary_am' => ['nullable', 'string'],
                    'bible_text_en' => ['nullable', 'string'],
                    'bible_text_am' => ['nullable', 'string'],
                ]);
                break;

            case 3:
                $request->validate([
                    'mezmurs' => ['nullable', 'array'],
                    'mezmurs.*.title_en' => ['nullable', 'string', 'max:255'],
                    'mezmurs.*.title_am' => ['nullable', 'string', 'max:255'],
                    'mezmurs.*.url' => ['nullable', 'url', 'max:500'],
                    'mezmurs.*.description_en' => ['nullable', 'string'],
                    'mezmurs.*.description_am' => ['nullable', 'string'],
                ]);
                $this->syncMezmurs($daily, $this->parseMezmurs($request));
                break;

            case 4:
                $updates = $request->validate([
                    'sinksar_title_en' => ['nullable', 'string', 'max:255'],
                    'sinksar_title_am' => ['nullable', 'string', 'max:255'],
                    'sinksar_url' => ['nullable', 'url', 'max:500'],
                    'sinksar_description_en' => ['nullable', 'string'],
                    'sinksar_description_am' => ['nullable', 'string'],
                ]);
                break;

            case 5:
                $request->validate([
                    'books' => ['nullable', 'array'],
                    'books.*.title_en' => ['nullable', 'string', 'max:255'],
                    'books.*.title_am' => ['nullable', 'string', 'max:255'],
                    'books.*.url' => ['nullable', 'url', 'max:500'],
                    'books.*.description_en' => ['nullable', 'string'],
                    'books.*.description_am' => ['nullable', 'string'],
                ]);
                $this->syncBooks($daily, $this->parseBooks($request));
                break;

            case 6:
                $request->validate([
                    'reflection_en' => ['nullable', 'string'],
                    'reflection_am' => ['nullable', 'string'],
                    'references' => ['nullable', 'array'],
                    'references.*.name_en' => ['nullable', 'string', 'max:255'],
                    'references.*.name_am' => ['nullable', 'string', 'max:255'],
                    'references.*.url' => ['nullable', 'url', 'max:500'],
                    'references.*.type' => ['nullable', 'string', 'in:video,website,file'],
                ]);
                $updates = [
                    'reflection_en' => $request->input('reflection_en'),
                    'reflection_am' => $request->input('reflection_am'),
                ];
                $this->syncReferences($daily, $this->parseReferences($request));
                break;

            case 7:
                $updates = $request->validate([
                    'is_published' => ['required', 'boolean'],
                ]);
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported step.',
                ], 422);
        }

        $updates['updated_by_id'] = auth()->id();

        if (! empty($updates)) {
            $daily->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => 'Step saved.',
            'step' => $step,
            'daily_id' => $daily->id,
            'next_step' => min($step + 1, 7),
        ]);
    }

    /**
     * Days that have content (for copy-from dropdown).
     * Excludes the given daily ID when editing.
     *
     * @return array<int, array{day_number: int, label: string}>
     */
    private function getDaysWithContent(?int $excludeDailyId): array
    {
        $season = LentSeason::active();
        if (! $season) {
            return [];
        }

        $query = DailyContent::where('lent_season_id', $season->id)
            ->orderBy('day_number')
            ->get(['id', 'day_number', 'date']);

        if ($excludeDailyId !== null) {
            $query = $query->filter(fn ($d) => (int) $d->id !== $excludeDailyId)->values();
        }

        return $query->map(fn ($d) => [
            'day_number' => (int) $d->day_number,
            'label' => sprintf('Day %d (%s)', $d->day_number, $d->date->format('M j')),
        ])->values()->toArray();
    }

    /**
     * Recent spiritual books from previous days for quick re-use.
     * Feature: Recommendations from previous days (click to add).
     *
     * @return array<int, array{title_en: string|null, title_am: string|null, url: string|null, description_en: string|null, description_am: string|null, day_number: int, date: string}>
     */
    private function getRecentBooks(?int $excludeDailyId): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('daily_content_books')) {
            return [];
        }

        $query = DailyContentBook::query()
            ->join('daily_contents', 'daily_content_books.daily_content_id', '=', 'daily_contents.id')
            ->select(
                'daily_content_books.title_en',
                'daily_content_books.title_am',
                'daily_content_books.url',
                'daily_content_books.description_en',
                'daily_content_books.description_am',
                'daily_contents.day_number',
                'daily_contents.date'
            )
            ->orderByDesc('daily_contents.date')
            ->limit(60);

        if ($excludeDailyId !== null) {
            $query->where('daily_content_books.daily_content_id', '!=', $excludeDailyId);
        }

        return $query->get()->map(function ($row) {
            return [
                'title_en' => $row->title_en,
                'title_am' => $row->title_am,
                'url' => $row->url,
                'description_en' => $row->description_en,
                'description_am' => $row->description_am,
                'day_number' => (int) $row->day_number,
                'date' => $row->date instanceof \DateTimeInterface ? $row->date->format('Y-m-d') : (string) $row->date,
            ];
        })->unique(fn (array $b) => trim(($b['title_en'] ?? '').'|'.($b['title_am'] ?? '')).'|'.($b['url'] ?? ''))->values()->toArray();
    }

    /**
     * Parse and filter books from request (keep only those with title_en or title_am).
     *
     * @return array<int, array{title_en: string|null, title_am: string|null, url: string|null, description_en: string|null, description_am: string|null}>
     */
    private function parseBooks(Request $request): array
    {
        $raw = $request->input('books', []);
        $parsed = [];
        foreach ($raw as $b) {
            $titleEn = trim((string) ($b['title_en'] ?? ''));
            $titleAm = trim((string) ($b['title_am'] ?? ''));
            if ($titleEn === '' && $titleAm === '') {
                continue;
            }
            $parsed[] = [
                'title_en' => $titleEn !== '' ? $titleEn : null,
                'title_am' => $titleAm !== '' ? $titleAm : null,
                'url' => trim((string) ($b['url'] ?? '')) ?: null,
                'description_en' => trim((string) ($b['description_en'] ?? '')) ?: null,
                'description_am' => trim((string) ($b['description_am'] ?? '')) ?: null,
            ];
        }

        return $parsed;
    }

    /**
     * @param  array<int, array{title_en: string|null, title_am: string|null, url: string|null, description_en: string|null, description_am: string|null}>  $books
     */
    private function syncBooks(DailyContent $daily, array $books): void
    {
        $daily->books()->delete();
        foreach ($books as $i => $b) {
            $daily->books()->create([
                'title_en' => $b['title_en'],
                'title_am' => $b['title_am'],
                'url' => $b['url'],
                'description_en' => $b['description_en'],
                'description_am' => $b['description_am'],
                'sort_order' => $i,
            ]);
        }
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
            'books' => ['nullable', 'array'],
            'books.*.title_en' => ['nullable', 'string', 'max:255'],
            'books.*.title_am' => ['nullable', 'string', 'max:255'],
            'books.*.url' => ['nullable', 'url', 'max:500'],
            'books.*.description_en' => ['nullable', 'string'],
            'books.*.description_am' => ['nullable', 'string'],
            'reflection_en' => ['nullable', 'string'],
            'reflection_am' => ['nullable', 'string'],
            'references' => ['nullable', 'array'],
            'references.*.name_en' => ['nullable', 'string', 'max:255'],
            'references.*.name_am' => ['nullable', 'string', 'max:255'],
            'references.*.url' => ['nullable', 'url', 'max:500'],
            'references.*.type' => ['nullable', 'string', 'in:video,website,file'],
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
     * @return array<int, array{name_en: string|null, name_am: string|null, url: string, type: string}>
     */
    private function parseReferences(Request $request): array
    {
        $raw = $request->input('references', []);
        $parsed = [];
        $allowed = ['video', 'website', 'file'];
        foreach ($raw as $r) {
            $nameEn = trim((string) ($r['name_en'] ?? ''));
            $nameAm = trim((string) ($r['name_am'] ?? ''));
            $url = trim((string) ($r['url'] ?? ''));
            $type = trim((string) ($r['type'] ?? 'website'));
            if (! in_array($type, $allowed, true)) {
                $type = 'website';
            }
            if (($nameEn !== '' || $nameAm !== '') && $url !== '') {
                $parsed[] = [
                    'name_en' => $nameEn !== '' ? $nameEn : null,
                    'name_am' => $nameAm !== '' ? $nameAm : null,
                    'url' => $url,
                    'type' => $type,
                ];
            }
        }

        return $parsed;
    }

    /**
     * @param  array<int, array{name_en: string|null, name_am: string|null, url: string, type: string}>  $references
     */
    private function syncReferences(DailyContent $daily, array $references): void
    {
        $daily->references()->delete();
        foreach ($references as $i => $ref) {
            $daily->references()->create([
                'name_en' => $ref['name_en'],
                'name_am' => $ref['name_am'],
                'url' => $ref['url'],
                'type' => $ref['type'] ?? 'website',
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Normalize incoming wizard step number.
     */
    private function normalizeStep(Request $request): int
    {
        return (int) ($request->integer('step', 1));
    }
}
