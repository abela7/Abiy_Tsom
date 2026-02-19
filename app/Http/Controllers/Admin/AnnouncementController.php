<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Manage announcements shown to members on the home page.
 */
class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with(['createdBy', 'updatedBy'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        $announcement = new Announcement;

        return view('admin.announcements.form', compact('announcement'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['nullable', 'image', 'max:2048'],
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'youtube_position' => ['nullable', 'string', 'in:top,end'],
            'button_enabled' => ['boolean'],
            'button_label' => ['nullable', 'required_if:button_enabled,true', 'string', 'max:100'],
            'button_label_en' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'required_if:button_enabled,true', 'string', 'max:500'],
        ]);

        $validated['button_enabled'] = $request->boolean('button_enabled');
        $validated['created_by_id'] = auth()->id();
        $validated['updated_by_id'] = auth()->id();

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')
                ->store('announcements', 'public');
        }

        Announcement::create($validated);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', __('app.announcement_created'));
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.form', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['nullable', 'image', 'max:2048'],
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'youtube_position' => ['nullable', 'string', 'in:top,end'],
            'button_enabled' => ['boolean'],
            'button_label' => ['nullable', 'required_if:button_enabled,true', 'string', 'max:100'],
            'button_label_en' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'required_if:button_enabled,true', 'string', 'max:500'],
        ]);

        $validated['button_enabled'] = $request->boolean('button_enabled');
        $validated['updated_by_id'] = auth()->id();

        if ($request->hasFile('photo')) {
            if ($announcement->photo) {
                Storage::disk('public')->delete($announcement->photo);
            }
            $validated['photo'] = $request->file('photo')
                ->store('announcements', 'public');
        }

        $announcement->update($validated);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', __('app.announcement_updated'));
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        if ($announcement->photo) {
            Storage::disk('public')->delete($announcement->photo);
        }

        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', __('app.announcement_deleted'));
    }
}
