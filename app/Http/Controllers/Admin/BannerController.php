<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\BannerResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(?Banner $editing = null): View
    {
        $banners = Banner::orderBy('sort_order')->orderByDesc('created_at')->get();

        $editing = request()->query('edit')
            ? Banner::find(request()->query('edit'))
            : null;

        return view('admin.banners.index', compact('banners', 'editing'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'title_am'       => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'description_am' => ['nullable', 'string', 'max:2000'],
            'image'          => ['nullable', 'image', 'max:2048'],
            'image_en'       => ['nullable', 'image', 'max:2048'],
            'button_label'   => ['nullable', 'string', 'max:255'],
            'button_label_am' => ['nullable', 'string', 'max:255'],
            'button_url'     => ['nullable', 'url', 'max:500'],
            'is_active'      => ['boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        if ($request->hasFile('image_en')) {
            $data['image_en'] = $request->file('image_en')->store('banners', 'public');
        }

        if (empty($data['button_label'])) {
            $data['button_label'] = "I'm Interested";
        }

        Banner::create($data);

        return redirect('/admin/banners')->with('success', __('app.banner_admin_saved'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'title_am'       => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'description_am' => ['nullable', 'string', 'max:2000'],
            'image'          => ['nullable', 'image', 'max:2048'],
            'image_en'       => ['nullable', 'image', 'max:2048'],
            'button_label'   => ['nullable', 'string', 'max:255'],
            'button_label_am' => ['nullable', 'string', 'max:255'],
            'button_url'     => ['nullable', 'url', 'max:500'],
            'is_active'      => ['boolean'],
            'sort_order'     => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        // Default image (Amharic / fallback)
        if ($request->boolean('remove_image')) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = null;
        } elseif ($request->hasFile('image')) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        // English image
        if ($request->boolean('remove_image_en')) {
            if ($banner->image_en) {
                Storage::disk('public')->delete($banner->image_en);
            }
            $data['image_en'] = null;
        } elseif ($request->hasFile('image_en')) {
            if ($banner->image_en) {
                Storage::disk('public')->delete($banner->image_en);
            }
            $data['image_en'] = $request->file('image_en')->store('banners', 'public');
        }

        if (empty($data['button_label'])) {
            $data['button_label'] = "I'm Interested";
        }

        $banner->update($data);

        return redirect('/admin/banners')->with('success', __('app.banner_admin_saved'));
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }
        if ($banner->image_en) {
            Storage::disk('public')->delete($banner->image_en);
        }

        $banner->delete();

        return redirect('/admin/banners')->with('success', __('app.banner_admin_deleted'));
    }

    public function toggleActive(Banner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return redirect('/admin/banners')->with('success', __('app.banner_admin_saved'));
    }
}
