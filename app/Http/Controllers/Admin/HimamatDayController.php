<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HimamatDay;
use App\Models\HimamatDayFaq;
use App\Models\LentSeason;
use App\Services\HimamatScaffoldService;
use App\Services\HimamatSynaxariumService;
use App\Services\HimamatTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HimamatDayController extends Controller
{
    public function index(): View
    {
        $season = LentSeason::active();
        $days = $season
            ? HimamatDay::query()
                ->where('lent_season_id', $season->id)
                ->with(['slots', 'faqs'])
                ->orderBy('date')
                ->orderBy('sort_order')
                ->get()
            : collect();

        return view('admin.himamat.index', [
            'season' => $season,
            'days' => $days,
        ]);
    }

    public function scaffold(HimamatScaffoldService $scaffold): RedirectResponse
    {
        $result = $scaffold->scaffoldActiveSeason(auth()->id());

        if (! $result['season']) {
            return redirect()->route('admin.himamat.index')
                ->with('error', __('app.no_active_season'));
        }

        return redirect()->route('admin.himamat.index')
            ->with('success', __('app.himamat_scaffold_success', [
                'days' => $result['created_days'],
                'slots' => $result['created_slots'],
            ]));
    }

    public function edit(string $day, HimamatSynaxariumService $synaxarium): View
    {
        $himamatDay = $this->resolveDay($day);
        $ethDateInfo = $synaxarium->resolveDateInfo($himamatDay, app()->getLocale());

        return view('admin.himamat.edit', [
            'day' => $himamatDay,
            'season' => $himamatDay->lentSeason,
            'ethDateInfo' => $ethDateInfo,
            'ethiopianMonthOptions' => $synaxarium->monthOptions(app()->getLocale()),
        ]);
    }

    public function preview(
        string $day,
        HimamatTimelineService $timeline,
        HimamatSynaxariumService $synaxarium
    ): View {
        $himamatDay = $this->resolveDay($day);
        $timelineData = $timeline->buildTimeline(
            $himamatDay->load([
                'slots' => fn ($query) => $query->orderBy('slot_order'),
                'faqs' => fn ($query) => $query->orderBy('sort_order'),
            ]),
            (string) ($himamatDay->slots->first()?->slot_key ?? 'intro')
        );
        $ethDateInfo = $synaxarium->resolveDateInfo($himamatDay, app()->getLocale());

        return view('member.himamat.day', [
            'member' => null,
            'day' => $himamatDay,
            'timeline' => $timelineData,
            'ethDateInfo' => $ethDateInfo,
            'previousDay' => null,
            'nextDay' => null,
            'publicPreview' => true,
            'previewMode' => true,
            'backUrl' => route('admin.himamat.edit', ['day' => $himamatDay->getKey()]),
        ]);
    }

    public function update(Request $request, string $day): RedirectResponse
    {
        $himamatDay = $this->resolveDay($day);
        $slotIds = $himamatDay->slots()->orderBy('slot_order')->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $faqIds = $himamatDay->faqs()->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $validated = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'spiritual_meaning_en' => ['nullable', 'string'],
            'spiritual_meaning_am' => ['nullable', 'string'],
            'ritual_guide_intro_en' => ['nullable', 'string'],
            'ritual_guide_intro_am' => ['nullable', 'string'],
            'synaxarium_source' => ['required', 'string', Rule::in(['automatic', 'manual'])],
            'synaxarium_month' => ['nullable', 'integer', 'min:1', 'max:13', 'required_if:synaxarium_source,manual'],
            'synaxarium_day' => ['nullable', 'integer', 'min:1', 'max:30', 'required_if:synaxarium_source,manual'],
            'is_published' => ['nullable', 'boolean'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.id' => ['nullable', 'integer'],
            'faqs.*.question_en' => ['nullable', 'string', 'max:255'],
            'faqs.*.question_am' => ['nullable', 'string', 'max:255'],
            'faqs.*.answer_en' => ['nullable', 'string'],
            'faqs.*.answer_am' => ['nullable', 'string'],
            'slots' => ['required', 'array', 'size:5'],
            'slots.*.id' => ['required', 'integer'],
            'slots.*.slot_header_en' => ['required', 'string', 'max:255'],
            'slots.*.slot_header_am' => ['nullable', 'string', 'max:255'],
            'slots.*.reminder_header_en' => ['required', 'string', 'max:255'],
            'slots.*.reminder_header_am' => ['nullable', 'string', 'max:255'],
            'slots.*.spiritual_significance_en' => ['nullable', 'string'],
            'slots.*.spiritual_significance_am' => ['nullable', 'string'],
            'slots.*.reading_reference_en' => ['nullable', 'string', 'max:255'],
            'slots.*.reading_reference_am' => ['nullable', 'string', 'max:255'],
            'slots.*.reading_text_en' => ['nullable', 'string'],
            'slots.*.reading_text_am' => ['nullable', 'string'],
            'slots.*.prostration_count' => ['nullable', 'integer', 'min:0', 'max:500'],
            'slots.*.prostration_guidance_en' => ['nullable', 'string'],
            'slots.*.prostration_guidance_am' => ['nullable', 'string'],
            'slots.*.short_prayer_en' => ['nullable', 'string'],
            'slots.*.short_prayer_am' => ['nullable', 'string'],
            'slots.*.is_published' => ['nullable', 'boolean'],
        ]);

        $faqPayloads = $this->normalizeFaqPayloads($validated['faqs'] ?? [], $faqIds);

        DB::transaction(function () use ($faqPayloads, $himamatDay, $request, $slotIds, $validated): void {
            $himamatDay->update([
                'title_en' => $validated['title_en'],
                'title_am' => $validated['title_am'] ?: null,
                'date' => $validated['date'],
                'spiritual_meaning_en' => $validated['spiritual_meaning_en'] ?: null,
                'spiritual_meaning_am' => $validated['spiritual_meaning_am'] ?: null,
                'ritual_guide_intro_en' => $validated['ritual_guide_intro_en'] ?: null,
                'ritual_guide_intro_am' => $validated['ritual_guide_intro_am'] ?: null,
                'synaxarium_source' => $validated['synaxarium_source'],
                'synaxarium_month' => $validated['synaxarium_source'] === 'manual'
                    ? (int) $validated['synaxarium_month']
                    : null,
                'synaxarium_day' => $validated['synaxarium_source'] === 'manual'
                    ? (int) $validated['synaxarium_day']
                    : null,
                'is_published' => $request->boolean('is_published'),
                'updated_by_id' => auth()->id(),
            ]);

            $slots = $himamatDay->slots()->get()->keyBy('id');
            foreach ($validated['slots'] as $slotInput) {
                $slotId = (string) $slotInput['id'];
                if (! in_array($slotId, $slotIds, true)) {
                    continue;
                }

                $slot = $slots->get((int) $slotInput['id']);
                if (! $slot) {
                    continue;
                }

                $slot->update([
                    'slot_header_en' => $slotInput['slot_header_en'],
                    'slot_header_am' => $slotInput['slot_header_am'] ?: null,
                    'reminder_header_en' => $slotInput['reminder_header_en'],
                    'reminder_header_am' => $slotInput['reminder_header_am'] ?: null,
                    'spiritual_significance_en' => $slotInput['spiritual_significance_en'] ?: null,
                    'spiritual_significance_am' => $slotInput['spiritual_significance_am'] ?: null,
                    'reading_reference_en' => $slotInput['reading_reference_en'] ?: null,
                    'reading_reference_am' => $slotInput['reading_reference_am'] ?: null,
                    'reading_text_en' => $slotInput['reading_text_en'] ?: null,
                    'reading_text_am' => $slotInput['reading_text_am'] ?: null,
                    'prostration_count' => $slotInput['prostration_count'] ?? 0,
                    'prostration_guidance_en' => $slotInput['prostration_guidance_en'] ?: null,
                    'prostration_guidance_am' => $slotInput['prostration_guidance_am'] ?: null,
                    'short_prayer_en' => $slotInput['short_prayer_en'] ?: null,
                    'short_prayer_am' => $slotInput['short_prayer_am'] ?: null,
                    'is_published' => (bool) ($slotInput['is_published'] ?? false),
                    'updated_by_id' => auth()->id(),
                ]);
            }

            $this->syncFaqs($himamatDay, $faqPayloads);
        });

        return redirect()->route('admin.himamat.index')
            ->with('success', __('app.himamat_updated'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawFaqs
     * @param  list<string>  $faqIds
     * @return list<array<string, mixed>>
     */
    private function normalizeFaqPayloads(array $rawFaqs, array $faqIds): array
    {
        $normalized = [];

        foreach ($rawFaqs as $index => $faqInput) {
            $questionEn = trim((string) ($faqInput['question_en'] ?? ''));
            $questionAm = trim((string) ($faqInput['question_am'] ?? ''));
            $answerEn = trim((string) ($faqInput['answer_en'] ?? ''));
            $answerAm = trim((string) ($faqInput['answer_am'] ?? ''));
            $faqId = isset($faqInput['id']) && $faqInput['id'] !== ''
                ? (string) (int) $faqInput['id']
                : null;

            $hasAnyContent = $questionEn !== '' || $questionAm !== '' || $answerEn !== '' || $answerAm !== '';
            if (! $hasAnyContent) {
                continue;
            }

            if ($questionEn === '' || $answerEn === '') {
                throw ValidationException::withMessages([
                    "faqs.$index.question_en" => __('app.himamat_faq_requires_english'),
                ]);
            }

            if ($faqId !== null && ! in_array($faqId, $faqIds, true)) {
                throw ValidationException::withMessages([
                    "faqs.$index.id" => __('app.himamat_faq_invalid'),
                ]);
            }

            $normalized[] = [
                'id' => $faqId !== null ? (int) $faqId : null,
                'question_en' => $questionEn,
                'question_am' => $questionAm !== '' ? $questionAm : null,
                'answer_en' => $answerEn,
                'answer_am' => $answerAm !== '' ? $answerAm : null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $faqPayloads
     */
    private function syncFaqs(HimamatDay $day, array $faqPayloads): void
    {
        $existingFaqs = $day->faqs()->get()->keyBy('id');
        $keepIds = [];

        foreach ($faqPayloads as $index => $faqPayload) {
            $attributes = [
                'sort_order' => $index + 1,
                'question_en' => $faqPayload['question_en'],
                'question_am' => $faqPayload['question_am'],
                'answer_en' => $faqPayload['answer_en'],
                'answer_am' => $faqPayload['answer_am'],
                'updated_by_id' => auth()->id(),
            ];

            $faqId = $faqPayload['id'];
            if ($faqId !== null && $existingFaqs->has($faqId)) {
                /** @var HimamatDayFaq $faq */
                $faq = $existingFaqs->get($faqId);
                $faq->update($attributes);
                $keepIds[] = $faq->id;

                continue;
            }

            $faq = $day->faqs()->create($attributes + [
                'created_by_id' => auth()->id(),
            ]);
            $keepIds[] = $faq->id;
        }

        $deleteQuery = $day->faqs();
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }

        $deleteQuery->delete();
    }

    private function resolveDay(string $day): HimamatDay
    {
        return HimamatDay::query()
            ->with([
                'lentSeason',
                'slots' => fn ($query) => $query->orderBy('slot_order'),
                'faqs' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->findOrFail((int) $day);
    }
}
