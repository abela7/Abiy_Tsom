<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\LentSeason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manage checklist activities (admin-defined).
 */
class ActivityController extends Controller
{
    public function index(): View
    {
        $season = LentSeason::active();
        $activities = $season
            ? $season->activities()->orderBy('sort_order')->get()
            : collect();

        return view('admin.activities.index', compact('season', 'activities'));
    }

    public function create(): View
    {
        $season = LentSeason::active();

        return view('admin.activities.form', compact('season'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lent_season_id' => ['required', 'exists:lent_seasons,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $validated['name_en'] = $validated['name'];
        $validated['name_am'] = null;
        $validated['description_en'] = $validated['description'];
        $validated['description_am'] = null;
        $validated['name'] = $validated['name'];

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by_id'] = auth()->id();
        $validated['updated_by_id'] = auth()->id();
        Activity::create($validated);

        return redirect('/admin/activities')->with('success', 'Activity created.');
    }

    public function edit(Activity $activity): View
    {
        $season = LentSeason::active();

        return view('admin.activities.form', compact('season', 'activity'));
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $validated['name_en'] = $validated['name'];
        $validated['description_en'] = $validated['description'];

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['updated_by_id'] = auth()->id();
        $activity->update($validated);

        return redirect('/admin/activities')->with('success', 'Activity updated.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $activity->delete();

        return redirect('/admin/activities')->with('success', 'Activity deleted.');
    }
}
