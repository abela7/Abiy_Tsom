<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin review of public content suggestions.
 */
class ContentSuggestionController extends Controller
{
    /**
     * List all suggestions for admin review.
     */
    public function index(Request $request): View
    {
        $filter = $request->query('status', 'all');

        $query = ContentSuggestion::with(['user', 'usedBy'])
            ->orderByDesc('created_at');

        if ($filter !== 'all') {
            if ($filter === 'used') {
                $query->whereNotNull('used_at');
            } else {
                $query->where('status', $filter)->whereNull('used_at');
            }
        }

        $suggestions = $query->get();
        $counts = [
            'all'      => ContentSuggestion::count(),
            'pending'  => ContentSuggestion::where('status', 'pending')->whereNull('used_at')->count(),
            'used'     => ContentSuggestion::whereNotNull('used_at')->count(),
            'rejected' => ContentSuggestion::where('status', 'rejected')->count(),
        ];

        return view('admin.suggestions.index', compact('suggestions', 'filter', 'counts'));
    }

    /**
     * Mark a suggestion as "used" — records who used it and when.
     */
    public function markUsed(ContentSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update([
            'status'     => 'approved',
            'used_at'    => now(),
            'used_by_id' => auth()->id(),
        ]);

        return back()->with('success', __('app.suggest_marked_used'));
    }

    /**
     * Undo a "used" mark.
     */
    public function unmarkUsed(ContentSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update([
            'status'     => 'pending',
            'used_at'    => null,
            'used_by_id' => null,
        ]);

        return back()->with('success', __('app.suggest_unmarked_used'));
    }

    /**
     * Reject a suggestion.
     */
    public function reject(ContentSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update(['status' => 'rejected']);

        return back()->with('success', __('app.suggest_rejected'));
    }
}
