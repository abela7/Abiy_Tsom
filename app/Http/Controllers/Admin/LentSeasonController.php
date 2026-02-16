<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\WeeklyTheme;
use App\Services\AbiyTsomStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manage lent seasons (create/edit year config).
 */
class LentSeasonController extends Controller
{
    public function index(): View
    {
        $seasons = LentSeason::orderByDesc('year')->get();

        return view('admin.seasons.index', compact('seasons'));
    }

    public function create(): View
    {
        return view('admin.seasons.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2024', 'unique:lent_seasons,year'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'total_days' => ['required', 'integer', 'min:50', 'max:60'],
        ]);

        // Deactivate all other seasons if this one is set active
        if ($request->boolean('is_active')) {
            LentSeason::query()->update(['is_active' => false]);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $season = LentSeason::create($validated);

        $this->generateWeeklyThemes($season);

        return redirect('/admin/seasons')->with('success', 'Season created with 8 weeks.');
    }

    public function edit(LentSeason $season): View
    {
        return view('admin.seasons.form', compact('season'));
    }

    public function update(Request $request, LentSeason $season): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2024', "unique:lent_seasons,year,{$season->id}"],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'total_days' => ['required', 'integer', 'min:50', 'max:60'],
        ]);

        if ($request->boolean('is_active')) {
            LentSeason::where('id', '!=', $season->id)->update(['is_active' => false]);
        }

        $oldStartDate = $season->start_date->format('Y-m-d');
        $validated['is_active'] = $request->boolean('is_active');
        $season->update($validated);
        $startDateChanged = $oldStartDate !== $validated['start_date'];

        if ($startDateChanged || $request->boolean('regenerate_weeks')) {
            $this->regenerateWeeklyThemes($season);
        }

        return redirect('/admin/seasons')->with('success', 'Season updated successfully.');
    }

    /**
     * Generate the 8 canonical weekly themes for a new season.
     */
    private function generateWeeklyThemes(LentSeason $season): void
    {
        $themes = AbiyTsomStructure::buildWeeklyThemesForSeason(
            $season->id,
            $season->start_date
        );
        foreach ($themes as $data) {
            WeeklyTheme::create($data);
        }
    }

    /**
     * Regenerate weekly themes when season dates change.
     */
    private function regenerateWeeklyThemes(LentSeason $season): void
    {
        $season->weeklyThemes()->delete();
        $this->generateWeeklyThemes($season);
    }
}
