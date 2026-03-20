<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EthiopianSynaxariumAnnual;
use App\Models\EthiopianSynaxariumMonthly;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SynaxariumController extends Controller
{
    public function index(): View
    {
        $monthlyCelebrations = EthiopianSynaxariumMonthly::orderBy('day')
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->get();

        $annualCelebrations = EthiopianSynaxariumAnnual::orderBy('month')
            ->orderBy('day')
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->get();

        $monthlyByDay = $monthlyCelebrations->groupBy('day');
        $annualByMonthDay = $annualCelebrations->groupBy(fn ($item) => $item->month.'-'.$item->day);

        $editingMonthly = request()->query('edit_monthly')
            ? EthiopianSynaxariumMonthly::find(request()->query('edit_monthly'))
            : null;

        $editingAnnual = request()->query('edit_annual')
            ? EthiopianSynaxariumAnnual::find(request()->query('edit_annual'))
            : null;

        return view('admin.synaxarium.index', compact(
            'monthlyCelebrations',
            'annualCelebrations',
            'monthlyByDay',
            'annualByMonthDay',
            'editingMonthly',
            'editingAnnual',
        ));
    }

    /**
     * One-page form: pick one calendar slot (monthly day, or annual month+day), then many saints.
     */
    public function bulkCreate(): View
    {
        $queryKind = request()->query('kind');
        $kind = $queryKind === 'annual' ? 'annual' : 'monthly';
        $day = min(30, max(1, (int) request()->query('day', 1)));
        $month = min(13, max(1, (int) request()->query('month', 1)));

        $defaultEntries = [];
        for ($i = 0; $i < 8; $i++) {
            $defaultEntries[] = $this->emptyBulkEntry();
        }

        return view('admin.synaxarium.bulk', [
            'defaultKind' => old('kind', $kind),
            'defaultDay' => old('day', $day),
            'defaultMonth' => old('month', $month),
            'defaultEntries' => old('entries', $defaultEntries),
            'emptyBulkEntry' => $this->emptyBulkEntry(),
        ]);
    }

    /**
     * Persist multiple saints for a single monthly day or annual month+day (no images).
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        $kind = (($request->input('kind') ?? 'monthly') === 'annual') ? 'annual' : 'monthly';

        $contextValidator = Validator::make(
            [
                'kind' => $kind,
                'day' => $request->input('day'),
                'month' => $request->input('month'),
            ],
            [
                'kind' => ['required', Rule::in(['monthly', 'annual'])],
                'day' => ['required', 'integer', 'min:1', 'max:30'],
                'month' => [Rule::requiredIf($kind === 'annual'), 'nullable', 'integer', 'min:1', 'max:13'],
            ]
        );

        if ($contextValidator->fails()) {
            throw ValidationException::withMessages($contextValidator->errors()->toArray());
        }

        /** @var array{kind: string, day: int, month: int|null} $context */
        $context = $contextValidator->validated();
        $day = (int) $context['day'];
        $month = $kind === 'annual' ? (int) $context['month'] : null;

        $rawEntries = $request->input('entries', []);
        if (! is_array($rawEntries)) {
            throw ValidationException::withMessages([
                'entries' => __('app.synaxarium_bulk_invalid'),
            ]);
        }

        $toCreate = [];
        $errorBag = new MessageBag;

        foreach ($rawEntries as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $celebrationEn = trim((string) ($row['celebration_en'] ?? ''));
            if ($celebrationEn === '') {
                continue;
            }

            $isMain = filter_var($row['is_main'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $payload = [
                'celebration_en' => $celebrationEn,
                'celebration_am' => trim((string) ($row['celebration_am'] ?? '')) ?: null,
                'description_en' => trim((string) ($row['description_en'] ?? '')) ?: null,
                'description_am' => trim((string) ($row['description_am'] ?? '')) ?: null,
                'is_main' => $isMain,
                'sort_order' => isset($row['sort_order']) && $row['sort_order'] !== ''
                    ? (int) $row['sort_order']
                    : 0,
            ];

            $rules = [
                'celebration_en' => ['required', 'string', 'max:500'],
                'celebration_am' => ['nullable', 'string', 'max:500'],
                'description_en' => ['nullable', 'string'],
                'description_am' => ['nullable', 'string'],
                'is_main' => ['boolean'],
                'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            ];

            $validator = Validator::make($payload, $rules);
            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $msgs) {
                    foreach ($msgs as $msg) {
                        $errorBag->add('entries.'.$index.'.'.$field, $msg);
                    }
                }

                continue;
            }

            $toCreate[] = $validator->validated();
        }

        if ($errorBag->isNotEmpty()) {
            throw ValidationException::withMessages($errorBag->getMessages());
        }

        if ($toCreate === []) {
            throw ValidationException::withMessages([
                'entries' => __('app.synaxarium_bulk_empty'),
            ]);
        }

        DB::transaction(function () use ($toCreate, $kind, $day, $month): void {
            foreach ($toCreate as $data) {
                $isMain = (bool) $data['is_main'];
                $sortOrder = (int) ($data['sort_order'] ?? 0);

                $base = [
                    'celebration_en' => $data['celebration_en'],
                    'celebration_am' => $data['celebration_am'],
                    'description_en' => $data['description_en'],
                    'description_am' => $data['description_am'],
                    'is_main' => $isMain,
                    'sort_order' => $sortOrder,
                    'image_path' => null,
                ];

                if ($kind === 'monthly') {
                    if ($isMain) {
                        EthiopianSynaxariumMonthly::where('day', $day)
                            ->where('is_main', true)
                            ->update(['is_main' => false]);
                    }
                    EthiopianSynaxariumMonthly::create(array_merge($base, ['day' => $day]));
                } else {
                    if ($isMain) {
                        EthiopianSynaxariumAnnual::where('month', $month)
                            ->where('day', $day)
                            ->where('is_main', true)
                            ->update(['is_main' => false]);
                    }
                    EthiopianSynaxariumAnnual::create(array_merge($base, [
                        'month' => $month,
                        'day' => $day,
                    ]));
                }
            }
        });

        $count = count($toCreate);

        $redirectUrl = $kind === 'monthly'
            ? '/admin/synaxarium?day='.$day
            : '/admin/synaxarium?tab=annual&month='.$month.'&day='.$day;

        return redirect()
            ->to($redirectUrl)
            ->with('success', __('app.synaxarium_bulk_saved', ['count' => $count]));
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyBulkEntry(): array
    {
        return [
            'celebration_en' => '',
            'celebration_am' => '',
            'description_en' => '',
            'description_am' => '',
            'is_main' => false,
            'sort_order' => 0,
        ];
    }

    public function storeMonthly(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day' => ['required', 'integer', 'min:1', 'max:30'],
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_main' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['is_main'] = $request->boolean('is_main');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($data['is_main']) {
            EthiopianSynaxariumMonthly::where('day', $data['day'])
                ->where('is_main', true)
                ->update(['is_main' => false]);
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('synaxarium', 'public');
        }

        unset($data['image']);
        EthiopianSynaxariumMonthly::create($data);

        return redirect('/admin/synaxarium?day='.$data['day'])->with('success', __('app.synaxarium_saved'));
    }

    public function updateMonthly(Request $request, EthiopianSynaxariumMonthly $monthly): RedirectResponse
    {
        $data = $request->validate([
            'day' => ['required', 'integer', 'min:1', 'max:30'],
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_main' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['is_main'] = $request->boolean('is_main');
        $data['sort_order'] = $data['sort_order'] ?? $monthly->sort_order;

        if ($data['is_main']) {
            EthiopianSynaxariumMonthly::where('day', $data['day'])
                ->where('id', '!=', $monthly->id)
                ->where('is_main', true)
                ->update(['is_main' => false]);
        }

        if ($request->boolean('remove_image')) {
            if ($monthly->image_path) {
                Storage::disk('public')->delete($monthly->image_path);
            }
            $data['image_path'] = null;
        } elseif ($request->hasFile('image')) {
            if ($monthly->image_path) {
                Storage::disk('public')->delete($monthly->image_path);
            }
            $data['image_path'] = $request->file('image')->store('synaxarium', 'public');
        }

        unset($data['image']);
        $monthly->update($data);

        return redirect('/admin/synaxarium?day='.$data['day'])->with('success', __('app.synaxarium_saved'));
    }

    public function destroyMonthly(EthiopianSynaxariumMonthly $monthly): RedirectResponse
    {
        if ($monthly->image_path) {
            Storage::disk('public')->delete($monthly->image_path);
        }
        $day = $monthly->day;
        $monthly->delete();

        return redirect('/admin/synaxarium?day='.$day)->with('success', __('app.synaxarium_deleted'));
    }

    public function storeAnnual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:13'],
            'day' => ['required', 'integer', 'min:1', 'max:30'],
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_main' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['is_main'] = $request->boolean('is_main');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($data['is_main']) {
            EthiopianSynaxariumAnnual::where('month', $data['month'])
                ->where('day', $data['day'])
                ->where('is_main', true)
                ->update(['is_main' => false]);
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('synaxarium', 'public');
        }

        unset($data['image']);
        EthiopianSynaxariumAnnual::create($data);

        return redirect('/admin/synaxarium')->with('success', __('app.synaxarium_saved'));
    }

    public function updateAnnual(Request $request, EthiopianSynaxariumAnnual $annual): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:13'],
            'day' => ['required', 'integer', 'min:1', 'max:30'],
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string'],
            'description_am' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_main' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['is_main'] = $request->boolean('is_main');
        $data['sort_order'] = $data['sort_order'] ?? $annual->sort_order;

        if ($data['is_main']) {
            EthiopianSynaxariumAnnual::where('month', $data['month'])
                ->where('day', $data['day'])
                ->where('id', '!=', $annual->id)
                ->where('is_main', true)
                ->update(['is_main' => false]);
        }

        if ($request->boolean('remove_image')) {
            if ($annual->image_path) {
                Storage::disk('public')->delete($annual->image_path);
            }
            $data['image_path'] = null;
        } elseif ($request->hasFile('image')) {
            if ($annual->image_path) {
                Storage::disk('public')->delete($annual->image_path);
            }
            $data['image_path'] = $request->file('image')->store('synaxarium', 'public');
        }

        unset($data['image']);
        $annual->update($data);

        return redirect('/admin/synaxarium')->with('success', __('app.synaxarium_saved'));
    }

    public function destroyAnnual(EthiopianSynaxariumAnnual $annual): RedirectResponse
    {
        if ($annual->image_path) {
            Storage::disk('public')->delete($annual->image_path);
        }
        $annual->delete();

        return redirect('/admin/synaxarium')->with('success', __('app.synaxarium_deleted'));
    }

    public function convertMonthlyToAnnual(Request $request, EthiopianSynaxariumMonthly $monthly): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:13'],
        ]);

        EthiopianSynaxariumAnnual::create([
            'month' => $data['month'],
            'day' => $monthly->day,
            'celebration_en' => $monthly->celebration_en,
            'celebration_am' => $monthly->celebration_am,
            'description_en' => $monthly->description_en,
            'description_am' => $monthly->description_am,
            'image_path' => $monthly->image_path,
            'is_main' => $monthly->is_main,
            'sort_order' => $monthly->sort_order,
        ]);

        $monthly->update(['image_path' => null]);
        $monthly->delete();

        return redirect('/admin/synaxarium')->with('success', __('app.synaxarium_converted_to_annual'));
    }

    public function convertAnnualToMonthly(EthiopianSynaxariumAnnual $annual): RedirectResponse
    {
        EthiopianSynaxariumMonthly::create([
            'day' => $annual->day,
            'celebration_en' => $annual->celebration_en,
            'celebration_am' => $annual->celebration_am,
            'description_en' => $annual->description_en,
            'description_am' => $annual->description_am,
            'image_path' => $annual->image_path,
            'is_main' => $annual->is_main,
            'sort_order' => $annual->sort_order,
        ]);

        $annual->update(['image_path' => null]);
        $annual->delete();

        return redirect('/admin/synaxarium?day='.$annual->day)->with('success', __('app.synaxarium_converted_to_monthly'));
    }
}
