<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentSuggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdvancedSuggestionController extends Controller
{
    public function create(): View
    {
        return view('admin.advanced-suggest.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'content_area'       => ['required', Rule::in([
                'mezmur', 'bible_reading', 'synaxarium', 'synaxarium_celebration',
                'lectionary', 'spiritual_book', 'reference_resource', 'daily_message',
            ])],
            'ethiopian_month'    => ['nullable', 'integer', 'min:1', 'max:13'],
            'ethiopian_day'      => ['nullable', 'integer', 'min:1', 'max:30'],
            'entry_scope'        => ['nullable', Rule::in(['yearly', 'monthly'])],
            'first_language'     => ['nullable', Rule::in(['en', 'am'])],
            'resource_type'      => ['nullable', Rule::in(['video', 'website', 'file'])],
            'lectionary_section' => ['nullable', Rule::in(['title_description', 'pauline', 'catholic', 'acts', 'mesbak', 'gospel', 'qiddase'])],
            'is_main'            => ['nullable', 'boolean'],
            'sort_order'         => ['nullable', 'integer'],
            // Bilingual fields
            'title_en'           => ['nullable', 'string', 'max:500'],
            'title_am'           => ['nullable', 'string', 'max:500'],
            'url_en'             => ['nullable', 'string', 'max:1000'],
            'url_am'             => ['nullable', 'string', 'max:1000'],
            'reference_en'       => ['nullable', 'string', 'max:500'],
            'reference_am'       => ['nullable', 'string', 'max:500'],
            'content_detail_en'  => ['nullable', 'string', 'max:10000'],
            'content_detail_am'  => ['nullable', 'string', 'max:10000'],
            'lyrics_en'          => ['nullable', 'string', 'max:10000'],
            'lyrics_am'          => ['nullable', 'string', 'max:10000'],
            'text_en'            => ['nullable', 'string', 'max:10000'],
            'text_am'            => ['nullable', 'string', 'max:10000'],
            'summary_en'         => ['nullable', 'string', 'max:5000'],
            'summary_am'         => ['nullable', 'string', 'max:5000'],
        ]);

        $contentArea = $validated['content_area'];

        // Build structured payload (mirrors Telegram confirmSuggestion)
        $payload = array_filter([
            'content_area'    => $contentArea,
            'ethiopian_month' => $validated['ethiopian_month'] ?? null,
            'ethiopian_day'   => $validated['ethiopian_day'] ?? null,
            'entry_scope'     => $validated['entry_scope'] ?? null,
            'first_language'  => $validated['first_language'] ?? null,
            'resource_type'   => $validated['resource_type'] ?? null,
            'lectionary_section' => $validated['lectionary_section'] ?? null,
            'is_main'         => isset($validated['is_main']) ? (bool) $validated['is_main'] : null,
            'sort_order'      => isset($validated['sort_order']) ? (int) $validated['sort_order'] : null,
        ], fn ($v) => $v !== null && $v !== '');

        foreach (['en', 'am'] as $lang) {
            foreach (['title', 'url', 'reference', 'content_detail', 'lyrics', 'text', 'summary'] as $field) {
                $key = "{$field}_{$lang}";
                if (! empty($validated[$key])) {
                    $payload[$key] = $validated[$key];
                }
            }
        }

        // Determine language
        $hasEn = ! empty($payload['title_en']) || ! empty($payload['reference_en']) || ! empty($payload['content_detail_en']) || ! empty($payload['text_en']) || ! empty($payload['lyrics_en']);
        $hasAm = ! empty($payload['title_am']) || ! empty($payload['reference_am']) || ! empty($payload['content_detail_am']) || ! empty($payload['text_am']) || ! empty($payload['lyrics_am']);
        $language = ($hasEn && $hasAm) ? 'both' : ($hasAm ? 'am' : 'en');

        // Legacy columns
        $legacyTitle = $payload['title_en'] ?? $payload['title_am'] ?? null;
        $legacyUrl   = $payload['url_en'] ?? $payload['url_am'] ?? null;
        $legacyDetail = $payload['content_detail_en'] ?? $payload['content_detail_am'] ?? null;
        $legacyType = match ($contentArea) {
            'lectionary', 'bible_reading' => 'bible',
            'mezmur'                      => 'mezmur',
            'synaxarium'                  => 'sinksar',
            default                       => 'reference',
        };

        ContentSuggestion::create([
            'user_id'            => Auth::id(),
            'source'             => 'web_advanced',
            'type'               => $legacyType,
            'content_area'       => $contentArea,
            'language'           => $language,
            'ethiopian_month'    => $validated['ethiopian_month'] ?? null,
            'ethiopian_day'      => $validated['ethiopian_day'] ?? null,
            'entry_scope'        => $validated['entry_scope'] ?? null,
            'title'              => $legacyTitle,
            'url'                => $legacyUrl,
            'content_detail'     => $legacyDetail,
            'structured_payload' => $payload,
            'submitter_name'     => Auth::user()?->name,
            'status'             => 'pending',
        ]);

        return redirect()
            ->route('admin.advanced-suggestions.create')
            ->with('success', __('app.advanced_suggest_submitted'));
    }
}
