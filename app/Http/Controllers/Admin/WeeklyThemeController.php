<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\WeeklyTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'description' => ['nullable', 'string'],
            'gospel_reference' => ['nullable', 'string', 'max:500'],
            'epistles_reference' => ['nullable', 'string', 'max:500'],
            'psalm_reference' => ['nullable', 'string', 'max:255'],
            'liturgy' => ['nullable', 'string', 'max:255'],
            'theme_summary' => ['nullable', 'string'],
            'week_start_date' => ['required', 'date'],
            'week_end_date' => ['required', 'date', 'after_or_equal:week_start_date'],
        ]);

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
            'description' => ['nullable', 'string'],
            'gospel_reference' => ['nullable', 'string', 'max:500'],
            'epistles_reference' => ['nullable', 'string', 'max:500'],
            'psalm_reference' => ['nullable', 'string', 'max:255'],
            'liturgy' => ['nullable', 'string', 'max:255'],
            'theme_summary' => ['nullable', 'string'],
            'week_start_date' => ['required', 'date'],
            'week_end_date' => ['required', 'date', 'after_or_equal:week_start_date'],
        ]);

        $theme->update($validated);

        return redirect('/admin/themes')->with('success', 'Weekly theme updated.');
    }
}
