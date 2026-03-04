<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EthiopianSynaxariumAnnual;
use App\Models\EthiopianSynaxariumMonthly;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $annualByMonthDay = $annualCelebrations->groupBy(fn ($item) => $item->month . '-' . $item->day);

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

    public function storeMonthly(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day' => ['required', 'integer', 'min:1', 'max:30'],
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
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

        return redirect('/admin/synaxarium')->with('success', __('app.synaxarium_saved'));
    }

    public function updateMonthly(Request $request, EthiopianSynaxariumMonthly $monthly): RedirectResponse
    {
        $data = $request->validate([
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_main' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['is_main'] = $request->boolean('is_main');
        $data['sort_order'] = $data['sort_order'] ?? $monthly->sort_order;

        if ($data['is_main']) {
            EthiopianSynaxariumMonthly::where('day', $monthly->day)
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

        return redirect('/admin/synaxarium')->with('success', __('app.synaxarium_saved'));
    }

    public function destroyMonthly(EthiopianSynaxariumMonthly $monthly): RedirectResponse
    {
        if ($monthly->image_path) {
            Storage::disk('public')->delete($monthly->image_path);
        }
        $monthly->delete();

        return redirect('/admin/synaxarium')->with('success', __('app.synaxarium_deleted'));
    }

    public function storeAnnual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:13'],
            'day' => ['required', 'integer', 'min:1', 'max:30'],
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'description_am' => ['nullable', 'string', 'max:5000'],
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
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
            'description_en' => ['nullable', 'string', 'max:5000'],
            'description_am' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_main' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $data['is_main'] = $request->boolean('is_main');
        $data['sort_order'] = $data['sort_order'] ?? $annual->sort_order;

        if ($data['is_main']) {
            EthiopianSynaxariumAnnual::where('month', $annual->month)
                ->where('day', $annual->day)
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
}
