<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ContentSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentSuggestionController extends Controller
{
    /**
     * Show the public suggestion form.
     */
    public function show(): View
    {
        $authUser = Auth::user();

        return view('public.suggest', compact('authUser'));
    }

    /**
     * Store a new content suggestion submitted by the public.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type'           => ['required', Rule::in(['bible', 'mezmur', 'sinksar', 'book', 'reference'])],
            'language'       => ['required', Rule::in(['en', 'am'])],
            'submitter_name' => ['nullable', 'string', 'max:100'],
            'title'          => ['nullable', 'string', 'max:255'],
            'reference'      => ['nullable', 'string', 'max:500'],
            'author'         => ['nullable', 'string', 'max:255'],
            'content_detail' => ['nullable', 'string', 'max:5000'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        ContentSuggestion::create([
            ...$validated,
            'user_id'    => Auth::id(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('suggest')
            ->with('success', true);
    }

    /**
     * "My Suggestions" — logged-in writer/editor sees their own submissions.
     */
    public function my(): View
    {
        $suggestions = ContentSuggestion::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('admin.suggestions.my', compact('suggestions'));
    }
}
