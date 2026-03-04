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
        $monthlyCelebrations = EthiopianSynaxariumMonthly::orderBy('day')->get();
        $annualCelebrations = EthiopianSynaxariumAnnual::orderBy('month')->orderBy('day')->get();

        $editingMonthly = request()->query('edit_monthly')
            ? EthiopianSynaxariumMonthly::find(request()->query('edit_monthly'))
            : null;

        $editingAnnual = request()->query('edit_annual')
            ? EthiopianSynaxariumAnnual::find(request()->query('edit_annual'))
            : null;

        return view('admin.synaxarium.index', compact(
            'monthlyCelebrations',
            'annualCelebrations',
            'editingMonthly',
            'editingAnnual',
        ));
    }

    public function storeMonthly(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day' => ['required', 'integer', 'min:1', 'max:30', 'unique:ethiopian_synaxarium_monthly,day'],
            'celebration_en' => ['required', 'string', 'max:500'],
            'celebration_am' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

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
        ]);

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
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $exists = EthiopianSynaxariumAnnual::where('month', $data['month'])
            ->where('day', $data['day'])->exists();

        if ($exists) {
            return back()->withErrors(['day' => __('app.synaxarium_annual_exists')])->withInput();
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
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

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
