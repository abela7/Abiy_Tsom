<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\WeeklyTheme;
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

    public function edit(WeeklyTheme $theme): View
    {
        $season = LentSeason::active();

        return view('admin.themes.form', compact('season', 'theme'));
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
}
