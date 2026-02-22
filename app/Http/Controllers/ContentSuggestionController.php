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
     * Store one or more content suggestions submitted from the multi-item form.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language'                => ['required', Rule::in(['en', 'am'])],
            'submitter_name'          => ['nullable', 'string', 'max:100'],
            'notes'                   => ['nullable', 'string', 'max:2000'],
            'items'                   => ['required', 'array', 'min:1', 'max:20'],
            'items.*.type'            => ['required', Rule::in(['bible', 'mezmur', 'sinksar', 'book', 'reference'])],
            'items.*.title'           => ['nullable', 'string', 'max:255'],
            'items.*.reference'       => ['nullable', 'string', 'max:500'],
            'items.*.author'          => ['nullable', 'string', 'max:255'],
            'items.*.content_detail'  => ['nullable', 'string', 'max:5000'],
        ]);

        $shared = [
            'language'       => $validated['language'],
            'submitter_name' => $validated['submitter_name'] ?? null,
            'notes'          => $validated['notes'] ?? null,
            'user_id'        => Auth::id(),
            'ip_address'     => $request->ip(),
        ];

        $count = 0;
        foreach ($validated['items'] as $item) {
            ContentSuggestion::create([
                ...$shared,
                'type'           => $item['type'],
                'title'          => $item['title'] ?? null,
                'reference'      => $item['reference'] ?? null,
                'author'         => $item['author'] ?? null,
                'content_detail' => $item['content_detail'] ?? null,
            ]);
            $count++;
        }

        return redirect()
            ->route('suggest')
            ->with('success', $count);
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
