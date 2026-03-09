<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Writers/editors submit announcement suggestions; admins review and apply or reject.
 */
class AnnouncementSuggestionController extends Controller
{
    /**
     * List pending announcement suggestions (admin only).
     */
    public function index(): View
    {
        $suggestions = AnnouncementSuggestion::with(['announcement', 'submittedBy'])
            ->where('status', AnnouncementSuggestion::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.announcement-suggestions.index', compact('suggestions'));
    }

    /**
     * Store a suggestion from a writer/editor.
     */
    public function store(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
            'youtube_position' => ['nullable', 'string', 'in:top,end'],
            'youtube_url_en' => ['nullable', 'string', 'max:500'],
            'youtube_position_en' => ['nullable', 'string', 'in:top,end'],
            'button_enabled' => ['boolean'],
            'button_label' => ['nullable', 'required_if:button_enabled,true', 'string', 'max:100'],
            'button_label_en' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'required_if:button_enabled,true', 'string', 'max:500'],
            'button_url_en' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['button_enabled'] = $request->boolean('button_enabled');
        unset($validated['notes']);

        AnnouncementSuggestion::create([
            'announcement_id' => $announcement->id,
            'submitted_by_id' => auth()->id(),
            'payload' => $validated,
            'notes' => $request->input('notes'),
            'status' => AnnouncementSuggestion::STATUS_PENDING,
        ]);

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', __('app.announcement_suggestion_submitted'));
    }

    /**
     * Apply a suggestion to the announcement (admin only).
     */
    public function apply(AnnouncementSuggestion $suggestion): RedirectResponse
    {
        if (! $suggestion->isPending()) {
            return redirect()
                ->route('admin.announcement-suggestions.index')
                ->with('error', __('app.announcement_suggestion_already_processed'));
        }

        $announcement = $suggestion->announcement;
        $announcement->update(array_merge($suggestion->payload, [
            'updated_by_id' => auth()->id(),
        ]));

        $suggestion->update([
            'status' => AnnouncementSuggestion::STATUS_APPLIED,
            'applied_by_id' => auth()->id(),
            'applied_at' => now(),
        ]);

        return redirect()
            ->route('admin.announcement-suggestions.index')
            ->with('success', __('app.announcement_suggestion_applied'));
    }

    /**
     * Reject a suggestion (admin only).
     */
    public function reject(Request $request, AnnouncementSuggestion $suggestion): RedirectResponse
    {
        if (! $suggestion->isPending()) {
            return redirect()
                ->route('admin.announcement-suggestions.index')
                ->with('error', __('app.announcement_suggestion_already_processed'));
        }

        $suggestion->update([
            'status' => AnnouncementSuggestion::STATUS_REJECTED,
            'rejected_by_id' => auth()->id(),
            'rejected_at' => now(),
            'rejected_reason' => $request->input('rejected_reason'),
        ]);

        return redirect()
            ->route('admin.announcement-suggestions.index')
            ->with('success', __('app.announcement_suggestion_rejected'));
    }
}
