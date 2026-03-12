<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\DailyContent;
use App\Models\DailyContentBook;
use App\Models\DailyContentMezmur;
use App\Models\DailyContentSinksarImage;
use App\Models\LentSeason;
use App\Services\AbiyTsomStructure;
use App\Services\EthiopianCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

            $metaDate = \Illuminate\Support\Carbon::parse($meta['date'])->startOfDay();
            $theme = $themes->first(function ($item) use ($metaDate): bool {
                if (! $item->week_start_date || ! $item->week_end_date) {
                    return false;
                }

                return $metaDate->betweenIncluded(
                    $item->week_start_date->copy()->startOfDay(),
                    $item->week_end_date->copy()->endOfDay()
                );
            });

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
            ? $season->dailyContents()
                ->with(['weeklyTheme', 'createdBy', 'updatedBy', 'assignedTo'])
                ->withCount(['views', 'memberViews', 'anonymousViews'])
                ->orderBy('day_number')
                ->get()
            : collect();
        $canEdit = auth()->user()?->role === 'admin' || auth()->user()?->isSuperAdmin();

        return view('admin.daily.index', compact('season', 'contents', 'canEdit'));
    }

    /**
     * AJAX: Return view details for a daily content (member names + anonymous count).
     */
    public function viewDetails(DailyContent $daily): JsonResponse
    {
        $memberViews = $daily->memberViews()
            ->with('member:id,baptism_name')
            ->orderByDesc('viewed_at')
            ->get()
            ->map(fn ($v) => [
                'baptism_name' => $v->member?->baptism_name ?? '—',
                'viewed_at' => $v->viewed_at?->diffForHumans() ?? '—',
            ]);

        $anonymousCount = $daily->anonymousViews()->count();

        return response()->json([
            'success' => true,
            'members' => $memberViews,
            'anonymous_count' => $anonymousCount,
            'total' => $memberViews->count() + $anonymousCount,
        ]);
    }

    /**
     * Preview a day as members would see it (works for drafts too).
     */
    public function preview(DailyContent $daily, EthiopianCalendarService $ethCalendar): View
    {
        $daily->load(['weeklyTheme', 'mezmurs', 'references', 'books', 'sinksarImages']);
        $ethDateInfo = $ethCalendar->getDateInfo($daily->date, app()->getLocale());
        $activities = Activity::where('lent_season_id', $daily->lent_season_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $member = null;
        $checklist = collect();
        $customActivities = collect();
        $customChecklist = collect();
        $publicPreview = true;
        $backUrl = route('admin.daily.index');
        $prevDay = null;
        $nextDay = null;

        return view('member.day', compact(
            'member',
            'daily',
            'activities',
            'checklist',
            'customActivities',
            'customChecklist',
            'publicPreview',
            'backUrl',
            'ethDateInfo',
            'prevDay',
            'nextDay',
        ));
    }

    public function create(): View
    {
        $season = LentSeason::active();
        $themes = $season ? $season->weeklyThemes()->orderBy('week_number')->get() : collect();
        $dayRangesByWeek = $this->getDayRangesByWeek();
        $daily = new DailyContent;
        $initialStep = max(1, min(7, $this->normalizeStep(request())));
        $recentBooks = $this->getRecentBooks(null);
        $recentMezmurs = $this->getRecentMezmurs(null);
        $daysWithContent = $this->getDaysWithContent(null);

        return view('admin.daily.form', compact('season', 'themes', 'dayRangesByWeek', 'daily', 'initialStep', 'recentBooks', 'recentMezmurs', 'daysWithContent'));
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
            ->with(['mezmurs', 'books', 'references', 'sinksarImages'])
            ->first();

        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Day not found.'], 404);
        }

        $mezmurs = $source->mezmurs->map(fn ($m) => [
            'title_en' => $m->title_en ?? '',
            'title_am' => $m->title_am ?? '',
            'url_en' => $m->url_en ?? $m->url ?? '',
            'url_am' => $m->url_am ?? $m->url ?? '',
            'description_en' => $m->description_en ?? '',
            'description_am' => $m->description_am ?? '',
            'lyrics_en' => $m->lyrics_en ?? '',
            'lyrics_am' => $m->lyrics_am ?? '',
        ])->values()->toArray();

        $references = $source->references->map(fn ($r) => [
            'name_en' => $r->name_en ?? '',
            'name_am' => $r->name_am ?? '',
            'url_en' => $r->url_en ?? $r->url ?? '',
            'url_am' => $r->url_am ?? $r->url ?? '',
            'type' => $r->type ?? 'website',
        ])->values()->toArray();

        $books = $source->books->map(fn ($b) => [
            'title_en' => $b->title_en ?? '',
            'title_am' => $b->title_am ?? '',
            'url_en' => $b->url_en ?? $b->url ?? '',
            'url_am' => $b->url_am ?? $b->url ?? '',
            'description_en' => $b->description_en ?? '',
            'description_am' => $b->description_am ?? '',
        ])->values()->toArray();

        $sinksarImages = $source->sinksarImages->map(fn ($img) => [
            'path' => $img->image_path,
            'url' => $img->imageUrl(),
            'caption_en' => $img->caption_en ?? '',
            'caption_am' => $img->caption_am ?? '',
        ])->values()->toArray();

        if (empty($mezmurs)) {
            $mezmurs = [['title_en' => '', 'title_am' => '', 'url_en' => '', 'url_am' => '', 'description_en' => '', 'description_am' => '']];
        }
        if (empty($references)) {
            $references = [['name_en' => '', 'name_am' => '', 'url_en' => '', 'url_am' => '', 'type' => 'website']];
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
                'sinksar_url_en' => $source->sinksar_url_en ?? $source->sinksar_url ?? '',
                'sinksar_url_am' => $source->sinksar_url_am ?? $source->sinksar_url ?? '',
                'sinksar_text_en' => $source->sinksar_text_en ?? '',
                'sinksar_text_am' => $source->sinksar_text_am ?? '',
                'sinksar_description_en' => $source->sinksar_description_en ?? '',
                'sinksar_description_am' => $source->sinksar_description_am ?? '',
                'reflection_en' => $source->reflection_en ?? '',
                'reflection_am' => $source->reflection_am ?? '',
                'reflection_title_en' => $source->reflection_title_en ?? '',
                'reflection_title_am' => $source->reflection_title_am ?? '',
                'mezmurs' => $mezmurs,
                'references' => $references,
                'books' => $books,
                'sinksar_images' => $sinksarImages,
            ],
        ]);
    }

    public function edit(DailyContent $daily): View
    {
        $daily->load(['books', 'sinksarImages']);
        $season = LentSeason::active();
        $themes = $season ? $season->weeklyThemes()->orderBy('week_number')->get() : collect();
        $dayRangesByWeek = $this->getDayRangesByWeek();
        $initialStep = max(1, min(7, $this->normalizeStep(request())));
        $recentBooks = $this->getRecentBooks($daily->id);
        $recentMezmurs = $this->getRecentMezmurs($daily->id);
        $daysWithContent = $this->getDaysWithContent($daily->id);
        $canEdit = auth()->user()?->role === 'admin' || auth()->user()?->isSuperAdmin();

        return view('admin.daily.form', compact('season', 'themes', 'daily', 'dayRangesByWeek', 'initialStep', 'recentBooks', 'recentMezmurs', 'daysWithContent', 'canEdit'));
    }

    public function update(Request $request, DailyContent $daily): RedirectResponse
    {
        $validated = $this->validateContent($request, $daily);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['updated_by_id'] = auth()->id();

        $mezmurs = $this->parseMezmurs($request);
        $references = $this->parseReferences($request);
        $books = $this->parseBooks($request);
        $sinksarImages = $this->parseSinksarImages($request->input('sinksar_images', []));
        unset($validated['mezmurs'], $validated['references'], $validated['books'], $validated['sinksar_images']);

        $daily->update($validated);
        $this->syncMezmurs($daily, $mezmurs);
        $this->syncReferences($daily, $references);
        $this->syncBooks($daily, $books);
        $this->syncSinksarImages($daily, $sinksarImages);

        return redirect('/admin/daily')->with('success', 'Daily content updated.');
    }

    /**
     * Upload a Bible audio MP3 to Cloudflare R2 (AJAX).
     */
    public function uploadBibleAudio(Request $request): JsonResponse
    {
        $request->validate([
            'bible_audio' => ['required', 'file', 'mimes:mp3,mpeg,ogg,wav,m4a', 'max:51200'],
            'locale'      => ['required', 'in:en,am'],
        ]);

        $locale = $request->input('locale');
        $path   = $request->file('bible_audio')->storeAs(
            'bible-audio',
            uniqid("bible_{$locale}_", true).'.'.$request->file('bible_audio')->extension(),
            'r2'
        );

        $url = rtrim(config('filesystems.disks.r2.url'), '/').'/'.$path;

        return response()->json([
            'success' => true,
            'url'     => $url,
            'locale'  => $locale,
        ]);
    }

    /**
     * Delete a Bible audio file from Cloudflare R2 (AJAX).
     */
    public function deleteBibleAudio(Request $request): JsonResponse
    {
        $request->validate([
            'url'    => ['required', 'string', 'max:1000'],
            'locale' => ['required', 'in:en,am'],
        ]);

        $publicBase = rtrim(config('filesystems.disks.r2.url'), '/');
        $url        = $request->input('url');

        if (str_starts_with($url, $publicBase.'/bible-audio/')) {
            $path = ltrim(str_replace($publicBase, '', $url), '/');
            Storage::disk('r2')->delete($path);
        }

        return response()->json(['success' => true]);
    }

    public function uploadBookPdf(Request $request): JsonResponse
    {
        $request->validate([
            'book_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $path = $request->file('book_pdf')->store('daily-books', 'public');
        $url = url(Storage::disk('public')->url($path));

        return response()->json([
            'success' => true,
            'url' => $url,
            'url_en' => $url,
            'url_am' => $url,
        ]);
    }

    /**
     * Upload a single Sinksar saint image (AJAX).
     */
    public function uploadSinksarImage(Request $request): JsonResponse
    {
        $request->validate([
            'sinksar_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('sinksar_image')->store('sinksar-images', 'public');

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => url(Storage::disk('public')->url($path)),
        ]);
    }

    /**
     * Delete a single uploaded Sinksar image file (AJAX).
     */
    public function deleteSinksarImage(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = $request->input('path');
        if (str_starts_with($path, 'sinksar-images/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['success' => true]);
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
                    'bible_reference_en'  => ['nullable', 'string', 'max:255'],
                    'bible_reference_am'  => ['nullable', 'string', 'max:255'],
                    'bible_summary_en'    => ['nullable', 'string'],
                    'bible_summary_am'    => ['nullable', 'string'],
                    'bible_text_en'       => ['nullable', 'string'],
                    'bible_text_am'       => ['nullable', 'string'],
                    'bible_audio_url_en'  => ['nullable', 'string', 'max:1000'],
                    'bible_audio_url_am'  => ['nullable', 'string', 'max:1000'],
                ]);
                break;

            case 3:
                $request->validate([
                    'mezmurs' => ['nullable', 'array'],
                    'mezmurs.*.title_en' => ['nullable', 'string', 'max:255'],
                    'mezmurs.*.title_am' => ['nullable', 'string', 'max:255'],
                    'mezmurs.*.url_en' => ['nullable', 'url', 'max:500'],
                    'mezmurs.*.url_am' => ['nullable', 'url', 'max:500'],
                    'mezmurs.*.description_en' => ['nullable', 'string'],
                    'mezmurs.*.description_am' => ['nullable', 'string'],
                    'mezmurs.*.lyrics_en' => ['nullable', 'string'],
                    'mezmurs.*.lyrics_am' => ['nullable', 'string'],
                ]);
                $this->syncMezmurs($daily, $this->parseMezmurs($request));
                break;

                case 4:
                $updates = $request->validate([
                    'sinksar_title_en' => ['nullable', 'string', 'max:255'],
                    'sinksar_title_am' => ['nullable', 'string', 'max:255'],
                    'sinksar_url_en' => ['nullable', 'url', 'max:500'],
                    'sinksar_url_am' => ['nullable', 'url', 'max:500'],
                    'sinksar_text_en' => ['nullable', 'string'],
                    'sinksar_text_am' => ['nullable', 'string'],
                    'sinksar_description_en' => ['nullable', 'string'],
                    'sinksar_description_am' => ['nullable', 'string'],
                    'sinksar_images' => ['nullable', 'array', 'max:5'],
                    'sinksar_images.*.path' => ['required', 'string', 'max:500'],
                    'sinksar_images.*.caption_en' => ['nullable', 'string', 'max:255'],
                    'sinksar_images.*.caption_am' => ['nullable', 'string', 'max:255'],
                ]);
                $this->syncSinksarImages($daily, $this->parseSinksarImages($request->input('sinksar_images', [])));
                unset($updates['sinksar_images']);
                break;

            case 5:
                $request->validate([
                    'books' => ['nullable', 'array'],
                    'books.*.title_en' => ['nullable', 'string', 'max:255'],
                    'books.*.title_am' => ['nullable', 'string', 'max:255'],
                    'books.*.url_en' => ['nullable', 'url', 'max:500'],
                    'books.*.url_am' => ['nullable', 'url', 'max:500'],
                    'books.*.description_en' => ['nullable', 'string'],
                    'books.*.description_am' => ['nullable', 'string'],
                ]);
                $this->syncBooks($daily, $this->parseBooks($request));
                break;

            case 6:
                $request->validate([
                    'reflection_en' => ['nullable', 'string'],
                    'reflection_am' => ['nullable', 'string'],
                    'reflection_title_en' => ['nullable', 'string', 'max:255'],
                    'reflection_title_am' => ['nullable', 'string', 'max:255'],
                    'references' => ['nullable', 'array'],
                    'references.*.name_en' => ['nullable', 'string', 'max:255'],
                    'references.*.name_am' => ['nullable', 'string', 'max:255'],
                    'references.*.url_en' => ['nullable', 'url', 'max:500'],
                    'references.*.url_am' => ['nullable', 'url', 'max:500'],
                    'references.*.type' => ['nullable', 'string', 'in:video,website,file'],
                ]);
                $updates = [
                    'reflection_en' => $request->input('reflection_en'),
                    'reflection_am' => $request->input('reflection_am'),
                    'reflection_title_en' => $request->input('reflection_title_en'),
                    'reflection_title_am' => $request->input('reflection_title_am'),
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
     * @return array<int, array{title_en: string|null, title_am: string|null, url_en: string|null, url_am: string|null, url: string|null, description_en: string|null, description_am: string|null, day_number: int, date: string}>
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
                'daily_content_books.url_en',
                'daily_content_books.url_am',
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
                'url' => $row->url ?? null,
                'url_en' => $row->url_en ?? null,
                'url_am' => $row->url_am ?? null,
                'description_en' => $row->description_en,
                'description_am' => $row->description_am,
                'day_number' => (int) $row->day_number,
                'date' => $row->date instanceof \DateTimeInterface ? $row->date->format('Y-m-d') : (string) $row->date,
            ];
        })->unique(fn (array $b) => trim(($b['title_en'] ?? '').'|'.($b['title_am'] ?? '')).'|'.($b['url'] ?? ''))->values()->toArray();
    }

    /** @return array<int, array<string, mixed>> */
    private function getRecentMezmurs(?int $excludeDailyId): array
    {
        $query = DailyContentMezmur::query()
            ->join('daily_contents', 'daily_content_mezmurs.daily_content_id', '=', 'daily_contents.id')
            ->select(
                'daily_content_mezmurs.title_en',
                'daily_content_mezmurs.title_am',
                'daily_content_mezmurs.url',
                'daily_content_mezmurs.url_en',
                'daily_content_mezmurs.url_am',
                'daily_content_mezmurs.description_en',
                'daily_content_mezmurs.description_am',
                'daily_content_mezmurs.lyrics_en',
                'daily_content_mezmurs.lyrics_am',
                'daily_contents.day_number',
                'daily_contents.date'
            )
            ->orderByDesc('daily_contents.date')
            ->limit(80);

        if ($excludeDailyId !== null) {
            $query->where('daily_content_mezmurs.daily_content_id', '!=', $excludeDailyId);
        }

        return $query->get()->map(fn ($row) => [
            'title_en'       => $row->title_en,
            'title_am'       => $row->title_am,
            'url'            => $row->url ?? null,
            'url_en'         => $row->url_en ?? null,
            'url_am'         => $row->url_am ?? null,
            'description_en' => $row->description_en,
            'description_am' => $row->description_am,
            'lyrics_en'      => $row->lyrics_en,
            'lyrics_am'      => $row->lyrics_am,
            'day_number'     => (int) $row->day_number,
            'date'           => $row->date instanceof \DateTimeInterface ? $row->date->format('Y-m-d') : (string) $row->date,
        ])->unique(fn (array $m) => trim(($m['title_am'] ?? '').'|'.($m['url_am'] ?? '').($m['url_en'] ?? '')))->values()->toArray();
    }

    /**
     * Parse and filter books from request (keep only those with title_en or title_am).
     *
     * @return array<int, array{title_en: string|null, title_am: string|null, url_en: string|null, url_am: string|null, description_en: string|null, description_am: string|null}>
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
                'url_en' => trim((string) ($b['url_en'] ?? '')) ?: null,
                'url_am' => trim((string) ($b['url_am'] ?? '')) ?: null,
                'description_en' => trim((string) ($b['description_en'] ?? '')) ?: null,
                'description_am' => trim((string) ($b['description_am'] ?? '')) ?: null,
            ];
        }

        return $parsed;
    }

    /**
     * @param  array<int, array{title_en: string|null, title_am: string|null, url_en: string|null, url_am: string|null, description_en: string|null, description_am: string|null}>  $books
     */
    private function syncBooks(DailyContent $daily, array $books): void
    {
        $daily->books()->delete();
        foreach ($books as $i => $b) {
            $daily->books()->create([
                'title_en' => $b['title_en'],
                'title_am' => $b['title_am'],
                'url_en' => $b['url_en'] ?? null,
                'url_am' => $b['url_am'] ?? null,
                'url' => $b['url_en'] ?? $b['url_am'] ?? null,
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
        $season = LentSeason::active();
        if (! $season) {
            return [];
        }

        $seasonStart = $season->start_date?->copy()?->startOfDay();
        if (! $seasonStart) {
            return [];
        }

        $ranges = [];
        foreach ($season->weeklyThemes()->orderBy('week_number')->get() as $theme) {
            if (! $theme->week_start_date || ! $theme->week_end_date) {
                continue;
            }

            $start = $seasonStart->diffInDays($theme->week_start_date->copy()->startOfDay(), false) + 1;
            $end = $seasonStart->diffInDays($theme->week_end_date->copy()->startOfDay(), false) + 1;

            $start = max(1, $start);
            $end = min(55, $end);

            if ($end < $start) {
                continue;
            }

            $ranges[(int) $theme->week_number] = [$start, $end];
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
            'bible_text_en'      => ['nullable', 'string'],
            'bible_text_am'      => ['nullable', 'string'],
            'bible_audio_url_en' => ['nullable', 'string', 'max:1000'],
            'bible_audio_url_am' => ['nullable', 'string', 'max:1000'],
            'mezmurs' => ['nullable', 'array'],
            'mezmurs.*.title_en' => ['nullable', 'string', 'max:255'],
            'mezmurs.*.title_am' => ['nullable', 'string', 'max:255'],
            'mezmurs.*.url_en' => ['nullable', 'url', 'max:500'],
            'mezmurs.*.url_am' => ['nullable', 'url', 'max:500'],
            'mezmurs.*.description_en' => ['nullable', 'string'],
            'mezmurs.*.description_am' => ['nullable', 'string'],
            'mezmurs.*.lyrics_en' => ['nullable', 'string'],
            'mezmurs.*.lyrics_am' => ['nullable', 'string'],
            'sinksar_title_en' => ['nullable', 'string', 'max:255'],
            'sinksar_title_am' => ['nullable', 'string', 'max:255'],
            'sinksar_url_en' => ['nullable', 'url', 'max:500'],
            'sinksar_url_am' => ['nullable', 'url', 'max:500'],
            'sinksar_text_en' => ['nullable', 'string'],
            'sinksar_text_am' => ['nullable', 'string'],
            'sinksar_description_en' => ['nullable', 'string'],
            'sinksar_description_am' => ['nullable', 'string'],
            'sinksar_images' => ['nullable', 'array', 'max:5'],
            'sinksar_images.*.path' => ['required', 'string', 'max:500'],
            'sinksar_images.*.caption_en' => ['nullable', 'string', 'max:255'],
            'sinksar_images.*.caption_am' => ['nullable', 'string', 'max:255'],
            'books' => ['nullable', 'array'],
            'books.*.title_en' => ['nullable', 'string', 'max:255'],
            'books.*.title_am' => ['nullable', 'string', 'max:255'],
            'books.*.url_en' => ['nullable', 'url', 'max:500'],
            'books.*.url_am' => ['nullable', 'url', 'max:500'],
            'books.*.description_en' => ['nullable', 'string'],
            'books.*.description_am' => ['nullable', 'string'],
            'reflection_en' => ['nullable', 'string'],
            'reflection_am' => ['nullable', 'string'],
            'reflection_title_en' => ['nullable', 'string', 'max:255'],
            'reflection_title_am' => ['nullable', 'string', 'max:255'],
            'references' => ['nullable', 'array'],
            'references.*.name_en' => ['nullable', 'string', 'max:255'],
            'references.*.name_am' => ['nullable', 'string', 'max:255'],
            'references.*.url_en' => ['nullable', 'url', 'max:500'],
            'references.*.url_am' => ['nullable', 'url', 'max:500'],
            'references.*.type' => ['nullable', 'string', 'in:video,website,file'],
        ]);
    }

    /**
     * Parse and filter mezmurs from request (keep only those with title_en or title_am).
     *
     * @return array<int, array{title_en: string|null, title_am: string|null, url_en: string|null, url_am: string|null, description_en: string|null, description_am: string|null}>
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
                'url_en' => trim((string) ($m['url_en'] ?? '')) ?: null,
                'url_am' => trim((string) ($m['url_am'] ?? '')) ?: null,
                'description_en' => trim((string) ($m['description_en'] ?? '')) ?: null,
                'description_am' => trim((string) ($m['description_am'] ?? '')) ?: null,
                'lyrics_en' => trim((string) ($m['lyrics_en'] ?? '')) ?: null,
                'lyrics_am' => trim((string) ($m['lyrics_am'] ?? '')) ?: null,
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
                'url_en' => $m['url_en'] ?? null,
                'url_am' => $m['url_am'] ?? null,
                'url' => $m['url_en'] ?? $m['url_am'] ?? null,
                'description_en' => $m['description_en'],
                'description_am' => $m['description_am'],
                'lyrics_en' => $m['lyrics_en'] ?? null,
                'lyrics_am' => $m['lyrics_am'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Parse and filter references from request (keep only those with name_en or name_am and url).
     *
     * @return array<int, array{name_en: string|null, name_am: string|null, url_en: string|null, url_am: string|null, type: string}>
     */
    private function parseReferences(Request $request): array
    {
        $raw = $request->input('references', []);
        $parsed = [];
        $allowed = ['video', 'website', 'file'];
        foreach ($raw as $r) {
            $nameEn = trim((string) ($r['name_en'] ?? ''));
            $nameAm = trim((string) ($r['name_am'] ?? ''));
            $urlEn = trim((string) ($r['url_en'] ?? ''));
            $urlAm = trim((string) ($r['url_am'] ?? ''));
            $type = trim((string) ($r['type'] ?? 'website'));
            if (! in_array($type, $allowed, true)) {
                $type = 'website';
            }
            if (($nameEn !== '' || $nameAm !== '') && ($urlEn !== '' || $urlAm !== '')) {
                $parsed[] = [
                    'name_en' => $nameEn !== '' ? $nameEn : null,
                    'name_am' => $nameAm !== '' ? $nameAm : null,
                    'url_en' => $urlEn !== '' ? $urlEn : null,
                    'url_am' => $urlAm !== '' ? $urlAm : null,
                    'url' => $urlEn !== '' ? $urlEn : ($urlAm !== '' ? $urlAm : null),
                    'type' => $type,
                ];
            }
        }

        return $parsed;
    }

    /**
     * @param  array<int, array{name_en: string|null, name_am: string|null, url_en: string|null, url_am: string|null, url: string|null, type: string}>  $references
     */
    private function syncReferences(DailyContent $daily, array $references): void
    {
        $daily->references()->delete();
        foreach ($references as $i => $ref) {
            $daily->references()->create([
                'name_en' => $ref['name_en'],
                'name_am' => $ref['name_am'],
                'url_en' => $ref['url_en'],
                'url_am' => $ref['url_am'],
                'url' => $ref['url'] ?? ($ref['url_en'] ?? $ref['url_am'] ?? null),
                'type' => $ref['type'] ?? 'website',
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Parse sinksar images from request input (keep only valid paths).
     *
     * @return array<int, array{path: string, caption_en: string|null, caption_am: string|null}>
     */
    private function parseSinksarImages(array $raw): array
    {
        $parsed = [];
        foreach ($raw as $img) {
            $path = trim((string) ($img['path'] ?? ''));
            if ($path === '' || ! str_starts_with($path, 'sinksar-images/')) {
                continue;
            }
            $parsed[] = [
                'path' => $path,
                'caption_en' => trim((string) ($img['caption_en'] ?? '')) ?: null,
                'caption_am' => trim((string) ($img['caption_am'] ?? '')) ?: null,
            ];
        }

        return array_slice($parsed, 0, 5);
    }

    /**
     * Sync sinksar images: delete removed files, recreate records in order.
     *
     * @param  array<int, array{path: string, caption_en: string|null, caption_am: string|null}>  $images
     */
    private function syncSinksarImages(DailyContent $daily, array $images): void
    {
        $newPaths = array_column($images, 'path');

        // Delete orphaned image files from disk
        foreach ($daily->sinksarImages as $old) {
            if (! in_array($old->image_path, $newPaths, true)) {
                Storage::disk('public')->delete($old->image_path);
            }
        }

        // Recreate all records to maintain sort order
        $daily->sinksarImages()->delete();
        foreach ($images as $i => $img) {
            $daily->sinksarImages()->create([
                'image_path' => $img['path'],
                'caption_en' => $img['caption_en'],
                'caption_am' => $img['caption_am'],
                'sort_order' => $i,
            ]);
        }
    }

    public function destroy(DailyContent $daily): RedirectResponse
    {
        $daily->delete();

        return redirect('/admin/daily')->with('success', __('app.daily_deleted'));
    }

    /**
     * Normalize incoming wizard step number.
     */
    private function normalizeStep(Request $request): int
    {
        return (int) ($request->integer('step', 1));
    }
}
