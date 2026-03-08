<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\Lectionary;
use App\Models\WeeklyTheme;
use App\Services\EthiopianCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Manage the 8 weekly themes.
 */
class WeeklyThemeController extends Controller
{
    public function index(): View
    {
        $season = LentSeason::active();
        $themes = $season
            ? $season->weeklyThemes()->orderBy('week_number')->get()
            : collect();

        return view('admin.themes.index', compact('season', 'themes'));
    }

    public function create(): View
    {
        $season = LentSeason::active();

        return view('admin.themes.form', compact('season'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lent_season_id' => ['required', 'exists:lent_seasons,id'],
            'week_number' => ['required', 'integer', 'min:1', 'max:8'],
            'name_geez' => ['nullable', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'meaning' => ['required', 'string', 'max:255'],
            'meaning_am' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'gospel_reference' => ['nullable', 'string', 'max:500'],
            'epistles_reference' => ['nullable', 'string', 'max:500'],
            'psalm_reference' => ['nullable', 'string', 'max:255'],
            'liturgy' => ['nullable', 'string', 'max:255'],
            'theme_summary' => ['nullable', 'string'],
            'summary_am' => ['nullable', 'string'],
            'week_start_date' => ['required', 'date'],
            'week_end_date' => ['required', 'date', 'after_or_equal:week_start_date'],
            // Feature picture
            'feature_picture' => ['nullable', 'image', 'max:2048'],
            // Bible readings (reference in EN + AM, full text in EN + AM)
            'reading_1_reference' => ['nullable', 'string', 'max:255'],
            'reading_1_reference_am' => ['nullable', 'string', 'max:255'],
            'reading_1_text_en' => ['nullable', 'string'],
            'reading_1_text_am' => ['nullable', 'string'],
            'reading_2_reference' => ['nullable', 'string', 'max:255'],
            'reading_2_reference_am' => ['nullable', 'string', 'max:255'],
            'reading_2_text_en' => ['nullable', 'string'],
            'reading_2_text_am' => ['nullable', 'string'],
            'reading_3_reference' => ['nullable', 'string', 'max:255'],
            'reading_3_reference_am' => ['nullable', 'string', 'max:255'],
            'reading_3_text_en' => ['nullable', 'string'],
            'reading_3_text_am' => ['nullable', 'string'],
            // Psalm (reference EN + AM, full text EN + AM)
            'psalm_reference_am' => ['nullable', 'string', 'max:255'],
            'psalm_text_en' => ['nullable', 'string'],
            'psalm_text_am' => ['nullable', 'string'],
            // Gospel (reference EN + AM, full text EN + AM)
            'gospel_reference_am' => ['nullable', 'string', 'max:255'],
            'gospel_text_en' => ['nullable', 'string'],
            'gospel_text_am' => ['nullable', 'string'],
            // Epistles (reference EN + AM, full text EN + AM)
            'epistles_reference_am' => ['nullable', 'string', 'max:500'],
            'epistles_text_en' => ['nullable', 'string'],
            'epistles_text_am' => ['nullable', 'string'],
            // Liturgy (anaphora name EN + AM, full text EN + AM)
            'liturgy_am' => ['nullable', 'string', 'max:255'],
            'liturgy_text_en' => ['nullable', 'string'],
            'liturgy_text_am' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('feature_picture')) {
            $validated['feature_picture'] = $request->file('feature_picture')->store('themes', 'public');
        }

        WeeklyTheme::create($validated);

        return redirect('/admin/themes')->with('success', 'Weekly theme created.');
    }

    public function edit(WeeklyTheme $theme, EthiopianCalendarService $ethiopianCalendarService): View
    {
        $season = LentSeason::active();
        $importDefaults = null;

        if ($theme->week_start_date) {
            $ethDate = $ethiopianCalendarService->gregorianToEthiopian($theme->week_start_date->copy());
            $importDefaults = [
                'month' => $ethDate['month'],
                'day' => $ethDate['day'],
                'month_name_en' => $ethDate['month_name_en'],
            ];
        }

        return view('admin.themes.form', compact('season', 'theme', 'importDefaults'));
    }

    public function update(Request $request, WeeklyTheme $theme): RedirectResponse
    {
        $validated = $request->validate([
            'week_number' => ['required', 'integer', 'min:1', 'max:8'],
            'name_geez' => ['nullable', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_am' => ['nullable', 'string', 'max:255'],
            'meaning' => ['required', 'string', 'max:255'],
            'meaning_am' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'gospel_reference' => ['nullable', 'string', 'max:500'],
            'epistles_reference' => ['nullable', 'string', 'max:500'],
            'psalm_reference' => ['nullable', 'string', 'max:255'],
            'liturgy' => ['nullable', 'string', 'max:255'],
            'theme_summary' => ['nullable', 'string'],
            'summary_am' => ['nullable', 'string'],
            'week_start_date' => ['required', 'date'],
            'week_end_date' => ['required', 'date', 'after_or_equal:week_start_date'],
            // Feature picture
            'feature_picture' => ['nullable', 'image', 'max:2048'],
            'remove_feature_picture' => ['nullable', 'boolean'],
            // Bible readings (reference in EN + AM, full text in EN + AM)
            'reading_1_reference' => ['nullable', 'string', 'max:255'],
            'reading_1_reference_am' => ['nullable', 'string', 'max:255'],
            'reading_1_text_en' => ['nullable', 'string'],
            'reading_1_text_am' => ['nullable', 'string'],
            'reading_2_reference' => ['nullable', 'string', 'max:255'],
            'reading_2_reference_am' => ['nullable', 'string', 'max:255'],
            'reading_2_text_en' => ['nullable', 'string'],
            'reading_2_text_am' => ['nullable', 'string'],
            'reading_3_reference' => ['nullable', 'string', 'max:255'],
            'reading_3_reference_am' => ['nullable', 'string', 'max:255'],
            'reading_3_text_en' => ['nullable', 'string'],
            'reading_3_text_am' => ['nullable', 'string'],
            // Psalm (reference EN + AM, full text EN + AM)
            'psalm_reference_am' => ['nullable', 'string', 'max:255'],
            'psalm_text_en' => ['nullable', 'string'],
            'psalm_text_am' => ['nullable', 'string'],
            // Gospel (reference EN + AM, full text EN + AM)
            'gospel_reference_am' => ['nullable', 'string', 'max:255'],
            'gospel_text_en' => ['nullable', 'string'],
            'gospel_text_am' => ['nullable', 'string'],
            // Epistles (reference EN + AM, full text EN + AM)
            'epistles_reference_am' => ['nullable', 'string', 'max:500'],
            'epistles_text_en' => ['nullable', 'string'],
            'epistles_text_am' => ['nullable', 'string'],
            // Liturgy (anaphora name EN + AM, full text EN + AM)
            'liturgy_am' => ['nullable', 'string', 'max:255'],
            'liturgy_text_en' => ['nullable', 'string'],
            'liturgy_text_am' => ['nullable', 'string'],
        ]);

        // Handle feature picture removal or replacement
        if ($request->boolean('remove_feature_picture')) {
            if ($theme->feature_picture) {
                Storage::disk('public')->delete($theme->feature_picture);
            }
            $validated['feature_picture'] = null;
        } elseif ($request->hasFile('feature_picture')) {
            if ($theme->feature_picture) {
                Storage::disk('public')->delete($theme->feature_picture);
            }
            $validated['feature_picture'] = $request->file('feature_picture')->store('themes', 'public');
        }

        // Do not overwrite existing picture if no new one was uploaded
        if (! array_key_exists('feature_picture', $validated)) {
            unset($validated['feature_picture']);
        }

        $theme->update($validated);

        return redirect('/admin/themes')->with('success', 'Weekly theme updated.');
    }

    public function importLectionary(Request $request, WeeklyTheme $theme): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:13'],
            'day' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $lectionary = Lectionary::query()
            ->where('month', $validated['month'])
            ->where('day', $validated['day'])
            ->first();

        if (! $lectionary) {
            return redirect()
                ->route('admin.themes.edit', $theme)
                ->with('error', __('app.theme_import_lectionary_missing'));
        }

        $theme->update($this->mapLectionaryToTheme($lectionary));

        return redirect()
            ->route('admin.themes.edit', $theme)
            ->with('success', __('app.theme_import_lectionary_success'));
    }

    /**
     * @return array<string, string|null>
     */
    private function mapLectionaryToTheme(Lectionary $lectionary): array
    {
        $paulineReferenceEn = $this->buildReference(
            $lectionary->pauline_book_en,
            $lectionary->pauline_chapter,
            $lectionary->pauline_verses
        );
        $paulineReferenceAm = $this->buildReference(
            $lectionary->pauline_book_am,
            $lectionary->pauline_chapter,
            $lectionary->pauline_verses
        );
        $catholicReferenceEn = $this->buildReference(
            $lectionary->catholic_book_en,
            $lectionary->catholic_chapter,
            $lectionary->catholic_verses
        );
        $catholicReferenceAm = $this->buildReference(
            $lectionary->catholic_book_am,
            $lectionary->catholic_chapter,
            $lectionary->catholic_verses
        );
        $actsReferenceEn = $this->buildReference('Acts', $lectionary->acts_chapter, $lectionary->acts_verses);
        $actsReferenceAm = $this->buildReference('የሐዋርያት ሥራ', $lectionary->acts_chapter, $lectionary->acts_verses);
        $gospelReferenceEn = $this->buildReference(
            $lectionary->gospel_book_en,
            $lectionary->gospel_chapter,
            $lectionary->gospel_verses
        );
        $gospelReferenceAm = $this->buildReference(
            $lectionary->gospel_book_am,
            $lectionary->gospel_chapter,
            $lectionary->gospel_verses
        );
        $psalmReference = $this->buildReference(
            $lectionary->mesbak_psalm !== null ? 'Psalm' : null,
            $lectionary->mesbak_psalm,
            $lectionary->mesbak_verses,
            includeBook: false
        );

        return [
            'reading_1_reference' => $paulineReferenceEn,
            'reading_1_reference_am' => $paulineReferenceAm,
            'reading_1_text_en' => $lectionary->pauline_text_en,
            'reading_1_text_am' => $lectionary->pauline_text_am,
            'reading_2_reference' => $catholicReferenceEn,
            'reading_2_reference_am' => $catholicReferenceAm,
            'reading_2_text_en' => $lectionary->catholic_text_en,
            'reading_2_text_am' => $lectionary->catholic_text_am,
            'reading_3_reference' => $actsReferenceEn,
            'reading_3_reference_am' => $actsReferenceAm,
            'reading_3_text_en' => $lectionary->acts_text_en,
            'reading_3_text_am' => $lectionary->acts_text_am,
            'psalm_reference' => $psalmReference,
            'psalm_reference_am' => $psalmReference,
            'psalm_text_en' => $lectionary->mesbak_text_en,
            'psalm_text_am' => $lectionary->mesbak_text_am,
            'gospel_reference' => $gospelReferenceEn,
            'gospel_reference_am' => $gospelReferenceAm,
            'gospel_text_en' => $lectionary->gospel_text_en,
            'gospel_text_am' => $lectionary->gospel_text_am,
            'epistles_reference' => $this->combineValues([$paulineReferenceEn, $catholicReferenceEn], '; '),
            'epistles_reference_am' => $this->combineValues([$paulineReferenceAm, $catholicReferenceAm], '; '),
            'epistles_text_en' => $this->combineValues([$lectionary->pauline_text_en, $lectionary->catholic_text_en], "\n\n"),
            'epistles_text_am' => $this->combineValues([$lectionary->pauline_text_am, $lectionary->catholic_text_am], "\n\n"),
            'liturgy' => $lectionary->qiddase_en,
            'liturgy_am' => $lectionary->qiddase_am,
        ];
    }

    private function buildReference(
        ?string $book,
        int|string|null $chapter,
        ?string $verses,
        bool $includeBook = true
    ): ?string {
        $chapterValue = filled($chapter) ? (string) $chapter : null;
        $versesValue = filled($verses) ? trim((string) $verses) : null;

        if ($chapterValue === null && $versesValue === null && ! filled($book)) {
            return null;
        }

        $ref = $chapterValue ?? '';
        if ($versesValue !== null && $versesValue !== '') {
            $ref .= ($ref !== '' ? ':' : '').$versesValue;
        }

        if (! $includeBook || ! filled($book)) {
            return $ref !== '' ? $ref : null;
        }

        return trim($book.' '.$ref);
    }

    /**
     * @param  array<int, string|null>  $values
     */
    private function combineValues(array $values, string $separator): ?string
    {
        $filtered = array_values(array_filter(
            array_map(
                static fn (?string $value): ?string => filled($value) ? trim($value) : null,
                $values
            )
        ));

        if ($filtered === []) {
            return null;
        }

        return implode($separator, $filtered);
    }
}
