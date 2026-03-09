<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\DailyContentSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Writers/editors submit suggestions; admins review and apply or reject.
 */
class DailyContentSuggestionController extends Controller
{
    /**
     * List pending daily content suggestions (admin only).
     */
    public function index(Request $request): View
    {
        $query = DailyContentSuggestion::with(['dailyContent', 'submittedBy'])
            ->where('status', DailyContentSuggestion::STATUS_PENDING)
            ->orderByDesc('created_at');

        $suggestions = $query->paginate(15)->withQueryString();

        return view('admin.daily-suggestions.index', compact('suggestions'));
    }

    /**
     * Store a suggestion from a writer/editor.
     */
    public function store(Request $request, DailyContent $daily): RedirectResponse|JsonResponse
    {
        $payload = $request->all();
        $payload = $this->mergePayloadWithExisting($payload, $daily);
        $validated = $this->validatePayload($payload, $daily);

        DailyContentSuggestion::create([
            'daily_content_id' => $daily->id,
            'submitted_by_id' => auth()->id(),
            'payload' => $validated,
            'notes' => $request->input('notes'),
            'status' => DailyContentSuggestion::STATUS_PENDING,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('app.daily_suggestion_submitted'),
                'redirect' => route('admin.daily.index'),
            ]);
        }

        return redirect()
            ->route('admin.daily.index')
            ->with('success', __('app.daily_suggestion_submitted'));
    }

    /**
     * Apply a suggestion to the daily content (admin only).
     */
    public function apply(DailyContentSuggestion $suggestion): RedirectResponse
    {
        if (! $suggestion->isPending()) {
            return redirect()
                ->route('admin.daily-suggestions.index')
                ->with('error', __('app.daily_suggestion_already_processed'));
        }

        $daily = $suggestion->dailyContent;
        $payload = $this->mergePayloadWithExisting($suggestion->payload, $daily);

        $dailyContentController = app(DailyContentController::class);
        $request = Request::create('', 'PUT', $payload);
        $request->setUserResolver(fn () => auth()->user());
        $request->headers->set('Accept', 'application/json');
        $request->merge($payload);

        $dailyContentController->update($request, $daily);

        $suggestion->update([
            'status' => DailyContentSuggestion::STATUS_APPLIED,
            'applied_by_id' => auth()->id(),
            'applied_at' => now(),
        ]);

        return redirect()
            ->route('admin.daily-suggestions.index')
            ->with('success', __('app.daily_suggestion_applied'));
    }

    /**
     * Reject a suggestion (admin only).
     */
    public function reject(Request $request, DailyContentSuggestion $suggestion): RedirectResponse
    {
        if (! $suggestion->isPending()) {
            return redirect()
                ->route('admin.daily-suggestions.index')
                ->with('error', __('app.daily_suggestion_already_processed'));
        }

        $suggestion->update([
            'status' => DailyContentSuggestion::STATUS_REJECTED,
            'rejected_by_id' => auth()->id(),
            'rejected_at' => now(),
            'rejected_reason' => $request->input('rejected_reason'),
        ]);

        return redirect()
            ->route('admin.daily-suggestions.index')
            ->with('success', __('app.daily_suggestion_rejected'));
    }

    /**
     * Validate payload (same rules as DailyContentController::validateContent).
     *
     * @return array<string, mixed>
     */
    private function validatePayload(array $payload, DailyContent $daily): array
    {
        $request = new Request($payload);
        $dayUnique = "unique:daily_contents,day_number,{$daily->id},id,lent_season_id,".($payload['lent_season_id'] ?? $daily->lent_season_id);

        return validator($payload, [
            'lent_season_id' => ['required', 'exists:lent_seasons,id'],
            'weekly_theme_id' => ['required', 'exists:weekly_themes,id'],
            'day_number' => ['required', 'integer', 'min:1', 'max:55', $dayUnique],
            'date' => ['required', 'date'],
            'day_title_en' => ['nullable', 'string', 'max:255'],
            'day_title_am' => ['nullable', 'string', 'max:255'],
            'bible_reference_en' => ['nullable', 'string', 'max:255'],
            'bible_reference_am' => ['nullable', 'string', 'max:255'],
            'bible_summary_en' => ['nullable', 'string'],
            'bible_summary_am' => ['nullable', 'string'],
            'bible_text_en' => ['nullable', 'string'],
            'bible_text_am' => ['nullable', 'string'],
            'mezmurs' => ['nullable', 'array'],
            'mezmurs.*.title_en' => ['nullable', 'string', 'max:255'],
            'mezmurs.*.title_am' => ['nullable', 'string', 'max:255'],
            'mezmurs.*.url_en' => ['nullable', 'url', 'max:500'],
            'mezmurs.*.url_am' => ['nullable', 'url', 'max:500'],
            'mezmurs.*.description_en' => ['nullable', 'string'],
            'mezmurs.*.description_am' => ['nullable', 'string'],
            'sinksar_title_en' => ['nullable', 'string', 'max:255'],
            'sinksar_title_am' => ['nullable', 'string', 'max:255'],
            'sinksar_url_en' => ['nullable', 'url', 'max:500'],
            'sinksar_url_am' => ['nullable', 'url', 'max:500'],
            'sinksar_text_en' => ['nullable', 'string'],
            'sinksar_text_am' => ['nullable', 'string'],
            'sinksar_description_en' => ['nullable', 'string'],
            'sinksar_description_am' => ['nullable', 'string'],
            'sinksar_images' => ['nullable', 'array', 'max:5'],
            'sinksar_images.*.path' => ['required', 'string', 'max:500'],
            'sinksar_images.*.caption_en' => ['nullable', 'string', 'max:255'],
            'sinksar_images.*.caption_am' => ['nullable', 'string', 'max:255'],
            'books' => ['nullable', 'array'],
            'books.*.title_en' => ['nullable', 'string', 'max:255'],
            'books.*.title_am' => ['nullable', 'string', 'max:255'],
            'books.*.url_en' => ['nullable', 'url', 'max:500'],
            'books.*.url_am' => ['nullable', 'url', 'max:500'],
            'books.*.description_en' => ['nullable', 'string'],
            'books.*.description_am' => ['nullable', 'string'],
            'reflection_en' => ['nullable', 'string'],
            'reflection_am' => ['nullable', 'string'],
            'reflection_title_en' => ['nullable', 'string', 'max:255'],
            'reflection_title_am' => ['nullable', 'string', 'max:255'],
            'references' => ['nullable', 'array'],
            'references.*.name_en' => ['nullable', 'string', 'max:255'],
            'references.*.name_am' => ['nullable', 'string', 'max:255'],
            'references.*.url_en' => ['nullable', 'url', 'max:500'],
            'references.*.url_am' => ['nullable', 'url', 'max:500'],
            'references.*.type' => ['nullable', 'string', 'in:video,website,file'],
        ])->validate();
    }

    /**
     * Merge suggestion payload with existing daily content (suggestion overwrites).
     *
     * @return array<string, mixed>
     */
    private function mergePayloadWithExisting(array $payload, DailyContent $daily): array
    {
        $daily->load(['mezmurs', 'references', 'books', 'sinksarImages']);

        $base = [
            'lent_season_id' => $daily->lent_season_id,
            'weekly_theme_id' => $daily->weekly_theme_id,
            'day_number' => $daily->day_number,
            'date' => $daily->date->format('Y-m-d'),
            'day_title_en' => $daily->day_title_en,
            'day_title_am' => $daily->day_title_am,
            'bible_reference_en' => $daily->bible_reference_en,
            'bible_reference_am' => $daily->bible_reference_am,
            'bible_summary_en' => $daily->bible_summary_en,
            'bible_summary_am' => $daily->bible_summary_am,
            'bible_text_en' => $daily->bible_text_en,
            'bible_text_am' => $daily->bible_text_am,
            'sinksar_title_en' => $daily->sinksar_title_en,
            'sinksar_title_am' => $daily->sinksar_title_am,
            'sinksar_url_en' => $daily->sinksar_url_en ?? $daily->sinksar_url,
            'sinksar_url_am' => $daily->sinksar_url_am ?? $daily->sinksar_url,
            'sinksar_text_en' => $daily->sinksar_text_en,
            'sinksar_text_am' => $daily->sinksar_text_am,
            'sinksar_description_en' => $daily->sinksar_description_en,
            'sinksar_description_am' => $daily->sinksar_description_am,
            'reflection_en' => $daily->reflection_en,
            'reflection_am' => $daily->reflection_am,
            'reflection_title_en' => $daily->reflection_title_en,
            'reflection_title_am' => $daily->reflection_title_am,
            'is_published' => $daily->is_published,
            'mezmurs' => $daily->mezmurs->map(fn ($m) => [
                'title_en' => $m->title_en,
                'title_am' => $m->title_am,
                'url_en' => $m->url_en ?? $m->url,
                'url_am' => $m->url_am ?? $m->url,
                'description_en' => $m->description_en,
                'description_am' => $m->description_am,
            ])->values()->toArray(),
            'references' => $daily->references->map(fn ($r) => [
                'name_en' => $r->name_en,
                'name_am' => $r->name_am,
                'url_en' => $r->url_en ?? $r->url,
                'url_am' => $r->url_am ?? $r->url,
                'type' => $r->type ?? 'website',
            ])->values()->toArray(),
            'books' => $daily->books->map(fn ($b) => [
                'title_en' => $b->title_en,
                'title_am' => $b->title_am,
                'url_en' => $b->url_en ?? $b->url,
                'url_am' => $b->url_am ?? $b->url,
                'description_en' => $b->description_en,
                'description_am' => $b->description_am,
            ])->values()->toArray(),
            'sinksar_images' => $daily->sinksarImages->map(fn ($img) => [
                'path' => $img->image_path,
                'caption_en' => $img->caption_en,
                'caption_am' => $img->caption_am,
            ])->values()->toArray(),
        ];

        return array_replace_recursive($base, $payload);
    }
}
