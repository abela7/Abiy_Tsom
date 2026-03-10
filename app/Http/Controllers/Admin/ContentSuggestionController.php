<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentSuggestion;
use App\Models\DailyContent;
use App\Models\DailyContentBook;
use App\Models\DailyContentMezmur;
use App\Models\DailyContentReference;
use App\Models\DailyContentSinksarImage;
use App\Models\Lectionary;
use App\Models\LentSeason;
use App\Services\AbiyTsomStructure;
use App\Services\EthiopianCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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
     * Apply a suggestion directly as real content to the matching DailyContent day.
     */
    public function apply(ContentSuggestion $suggestion, EthiopianCalendarService $calendar): RedirectResponse
    {
        if (! $suggestion->ethiopian_month || ! $suggestion->ethiopian_day) {
            return back()->with('error', __('app.suggest_apply_no_date'));
        }

        $gregorianDate = $calendar->ethiopianToGregorian(
            (int) $suggestion->ethiopian_month,
            (int) $suggestion->ethiopian_day
        );

        $season = LentSeason::active();
        if (! $season) {
            return back()->with('error', __('app.suggest_apply_no_season'));
        }

        $daily = DailyContent::where('lent_season_id', $season->id)
            ->where('date', $gregorianDate->format('Y-m-d'))
            ->first();

        if (! $daily) {
            $daily = $this->autoCreateDailyContent($season, $gregorianDate);
            if (! $daily) {
                return back()->with('error', __('app.suggest_apply_no_daily', [
                    'date' => $suggestion->ethiopianDateLabel() ?? $gregorianDate->format('M d, Y'),
                ]));
            }
        }

        $payload = $suggestion->structured_payload ?? [];
        $area = (string) ($suggestion->content_area ?: $suggestion->type);

        if ($area === 'lectionary') {
            $this->applyLectionary($suggestion, $payload);
        } else {
            match ($area) {
                'bible_reading' => $this->applyBibleReading($daily, $payload),
                'synaxarium' => $this->applySynaxarium($daily, $payload, $suggestion),
                'mezmur' => $this->applyMezmur($daily, $payload),
                'spiritual_book' => $this->applyBook($daily, $payload),
                'reference_resource' => $this->applyReference($daily, $payload),
                'daily_message' => $this->applyDailyMessage($daily, $payload),
                default => null,
            };
        }

        $daily->update(['updated_by_id' => auth()->id()]);

        $suggestion->update([
            'status' => 'approved',
            'used_at' => now(),
            'used_by_id' => auth()->id(),
        ]);

        return back()->with('success', __('app.suggest_applied', [
            'date' => $suggestion->ethiopianDateLabel() ?? $gregorianDate->format('M d'),
        ]));
    }

    private function applyBibleReading(DailyContent $daily, array $payload): void
    {
        $updates = [];
        foreach (['en', 'am'] as $lang) {
            if (! empty($payload["reference_{$lang}"])) {
                $updates["bible_reference_{$lang}"] = $payload["reference_{$lang}"];
            }
            if (! empty($payload["summary_{$lang}"])) {
                $updates["bible_summary_{$lang}"] = $payload["summary_{$lang}"];
            }
            if (! empty($payload["text_{$lang}"])) {
                $updates["bible_text_{$lang}"] = $payload["text_{$lang}"];
            }
        }
        if ($updates !== []) {
            $daily->update($updates);
        }
    }

    private function applySynaxarium(DailyContent $daily, array $payload, ContentSuggestion $suggestion): void
    {
        $updates = [];
        foreach (['en', 'am'] as $lang) {
            if (! empty($payload["title_{$lang}"])) {
                $updates["sinksar_title_{$lang}"] = $payload["title_{$lang}"];
            }
            if (! empty($payload["content_detail_{$lang}"])) {
                $updates["sinksar_description_{$lang}"] = $payload["content_detail_{$lang}"];
            }
        }
        if ($updates !== []) {
            $daily->update($updates);
        }

        // Add sinksar image if present
        if (! empty($suggestion->image_path) && Storage::disk('public')->exists($suggestion->image_path)) {
            $maxSort = $daily->sinksarImages()->max('sort_order') ?? 0;
            DailyContentSinksarImage::create([
                'daily_content_id' => $daily->id,
                'image_path' => $suggestion->image_path,
                'caption_en' => $payload['title_en'] ?? null,
                'caption_am' => $payload['title_am'] ?? null,
                'sort_order' => $maxSort + 1,
            ]);
        }
    }

    private function applyMezmur(DailyContent $daily, array $payload): void
    {
        $maxSort = $daily->mezmurs()->max('sort_order') ?? 0;
        DailyContentMezmur::create([
            'daily_content_id' => $daily->id,
            'title_en' => $payload['title_en'] ?? null,
            'title_am' => $payload['title_am'] ?? null,
            'url_en' => $payload['url_en'] ?? null,
            'url_am' => $payload['url_am'] ?? null,
            'description_en' => $payload['content_detail_en'] ?? null,
            'description_am' => $payload['content_detail_am'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);
    }

    private function applyBook(DailyContent $daily, array $payload): void
    {
        $maxSort = $daily->books()->max('sort_order') ?? 0;
        DailyContentBook::create([
            'daily_content_id' => $daily->id,
            'title_en' => $payload['title_en'] ?? null,
            'title_am' => $payload['title_am'] ?? null,
            'url_en' => $payload['url_en'] ?? null,
            'url_am' => $payload['url_am'] ?? null,
            'description_en' => $payload['content_detail_en'] ?? null,
            'description_am' => $payload['content_detail_am'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);
    }

    private function applyReference(DailyContent $daily, array $payload): void
    {
        $maxSort = $daily->references()->max('sort_order') ?? 0;
        DailyContentReference::create([
            'daily_content_id' => $daily->id,
            'name_en' => $payload['title_en'] ?? null,
            'name_am' => $payload['title_am'] ?? null,
            'url_en' => $payload['url_en'] ?? null,
            'url_am' => $payload['url_am'] ?? null,
            'type' => $payload['resource_type'] ?? DailyContentReference::TYPE_WEBSITE,
            'sort_order' => $maxSort + 1,
        ]);
    }

    private function applyDailyMessage(DailyContent $daily, array $payload): void
    {
        $updates = [];
        foreach (['en', 'am'] as $lang) {
            if (! empty($payload["title_{$lang}"])) {
                $updates["reflection_title_{$lang}"] = $payload["title_{$lang}"];
            }
            if (! empty($payload["content_detail_{$lang}"])) {
                $updates["reflection_{$lang}"] = $payload["content_detail_{$lang}"];
            }
        }
        if ($updates !== []) {
            $daily->update($updates);
        }
    }

    private function applyLectionary(ContentSuggestion $suggestion, array $payload): void
    {
        $month = (int) $suggestion->ethiopian_month;
        $day = (int) $suggestion->ethiopian_day;
        $section = (string) ($payload['lectionary_section'] ?? '');

        $lectionary = Lectionary::firstOrCreate(
            ['month' => $month, 'day' => $day],
        );

        $updates = match ($section) {
            'title_description' => array_filter([
                'title_en' => $payload['title_en'] ?? null,
                'title_am' => $payload['title_am'] ?? null,
                'description_en' => $payload['content_detail_en'] ?? null,
                'description_am' => $payload['content_detail_am'] ?? null,
            ]),
            'pauline' => array_filter([
                'pauline_chapter' => $payload['lectionary_chapter'] ?? null,
                'pauline_verses' => $payload['lectionary_verse_range'] ?? null,
                'pauline_book_en' => $payload['lectionary_book_label'] ?? null,
                'pauline_text_en' => $payload['content_detail_en'] ?? null,
                'pauline_text_am' => $payload['content_detail_am'] ?? null,
            ]),
            'catholic' => array_filter([
                'catholic_chapter' => $payload['lectionary_chapter'] ?? null,
                'catholic_verses' => $payload['lectionary_verse_range'] ?? null,
                'catholic_book_en' => $payload['lectionary_book_label'] ?? null,
                'catholic_text_en' => $payload['content_detail_en'] ?? null,
                'catholic_text_am' => $payload['content_detail_am'] ?? null,
            ]),
            'acts' => array_filter([
                'acts_chapter' => $payload['lectionary_chapter'] ?? null,
                'acts_verses' => $payload['lectionary_verse_range'] ?? null,
                'acts_text_en' => $payload['content_detail_en'] ?? null,
                'acts_text_am' => $payload['content_detail_am'] ?? null,
            ]),
            'mesbak' => array_filter([
                'mesbak_psalm' => $payload['lectionary_chapter'] ?? null,
                'mesbak_verses' => $payload['lectionary_verse_range'] ?? null,
                'mesbak_text_en' => $payload['content_detail_en'] ?? null,
                'mesbak_text_am' => $payload['content_detail_am'] ?? null,
            ]),
            'gospel' => array_filter([
                'gospel_chapter' => $payload['lectionary_chapter'] ?? null,
                'gospel_verses' => $payload['lectionary_verse_range'] ?? null,
                'gospel_book_en' => $payload['lectionary_book_label'] ?? null,
                'gospel_text_en' => $payload['content_detail_en'] ?? null,
                'gospel_text_am' => $payload['content_detail_am'] ?? null,
            ]),
            'qiddase' => array_filter([
                'qiddase_en' => $payload['title_en'] ?? $payload['content_detail_en'] ?? null,
                'qiddase_am' => $payload['title_am'] ?? $payload['content_detail_am'] ?? null,
            ]),
            default => [],
        };

        if ($updates !== []) {
            $lectionary->update($updates);
        }
    }

    /**
     * Auto-create a DailyContent entry for the given date within the active season.
     */
    private function autoCreateDailyContent(LentSeason $season, Carbon $gregorianDate): ?DailyContent
    {
        $themes = $season->weeklyThemes()->orderBy('week_number')->get()->keyBy('week_number');
        if ($themes->isEmpty()) {
            return null;
        }

        $targetDate = $gregorianDate->format('Y-m-d');
        $dayMeta = null;

        foreach (AbiyTsomStructure::buildDayMetadata($season->start_date) as $meta) {
            if ($meta['date'] === $targetDate) {
                $dayMeta = $meta;
                break;
            }
        }

        if (! $dayMeta) {
            return null;
        }

        $metaDate = Carbon::parse($dayMeta['date'])->startOfDay();
        $theme = $themes->first(function ($item) use ($metaDate): bool {
            if (! $item->week_start_date || ! $item->week_end_date) {
                return false;
            }

            return $metaDate->betweenIncluded(
                $item->week_start_date->copy()->startOfDay(),
                $item->week_end_date->copy()->endOfDay()
            );
        });

        if (! $theme) {
            return null;
        }

        return DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => $dayMeta['day_number'],
            'date' => $dayMeta['date'],
            'is_published' => false,
            'created_by_id' => auth()->id(),
            'updated_by_id' => auth()->id(),
        ]);
    }

    /**
     * Reject a suggestion.
     */
    public function reject(ContentSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update(['status' => 'rejected']);

        return back()->with('success', __('app.suggest_rejected'));
    }

    /**
     * Delete all suggestion records.
     */
    public function clearAll(): RedirectResponse
    {
        $count = ContentSuggestion::query()->delete();

        return redirect()->route('admin.suggestions.index')
            ->with('success', __('app.suggest_all_cleared', ['count' => $count]));
    }
}
