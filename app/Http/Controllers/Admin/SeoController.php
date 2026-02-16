<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Manage global SEO meta and sharing settings.
 */
class SeoController extends Controller
{
    /**
     * Show SEO settings form.
     */
    public function index(): View
    {
        $settings = SeoSetting::allCached();

        return view('admin.seo.index', compact('settings'));
    }

    /**
     * Update SEO settings.
     */
    public function update(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('seo_settings')) {
            return redirect()
                ->route('admin.seo.index')
                ->with('error', __('app.seo_migration_required'));
        }

        $validated = $request->validate([
            'site_title_en' => ['nullable', 'string', 'max:255'],
            'site_title_am' => ['nullable', 'string', 'max:255'],
            'meta_description_en' => ['nullable', 'string', 'max:1000'],
            'meta_description_am' => ['nullable', 'string', 'max:1000'],
            'og_title_en' => ['nullable', 'string', 'max:255'],
            'og_title_am' => ['nullable', 'string', 'max:255'],
            'og_description_en' => ['nullable', 'string', 'max:1000'],
            'og_description_am' => ['nullable', 'string', 'max:1000'],
            'twitter_card' => ['required', 'string', 'in:summary,summary_large_image'],
            'robots' => ['nullable', 'string', 'max:255'],
            'remove_og_image' => ['nullable', 'boolean'],
            'og_image' => ['nullable', 'image', 'max:4096'],
        ]);

        $currentImage = SeoSetting::cached('og_image');
        $nextImage = $currentImage;

        if ($request->boolean('remove_og_image')) {
            if ($currentImage) {
                Storage::disk('public')->delete($currentImage);
            }
            $nextImage = null;
        }

        if ($request->hasFile('og_image')) {
            if ($currentImage) {
                Storage::disk('public')->delete($currentImage);
            }
            $nextImage = $request->file('og_image')->store('seo', 'public');
        }

        unset($validated['og_image'], $validated['remove_og_image']);
        $validated['og_image'] = $nextImage;

        SeoSetting::upsertValues($validated);

        return redirect()
            ->route('admin.seo.index')
            ->with('success', __('app.seo_saved'));
    }
}

