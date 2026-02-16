@extends('layouts.admin')
@section('title', __('app.seo_settings'))

@section('content')
@php
    $siteTitleEn = old('site_title_en', $settings['site_title_en'] ?? __('app.app_name'));
    $siteTitleAm = old('site_title_am', $settings['site_title_am'] ?? __('app.app_name'));
    $metaDescriptionEn = old('meta_description_en', $settings['meta_description_en'] ?? __('app.meta_description'));
    $metaDescriptionAm = old('meta_description_am', $settings['meta_description_am'] ?? __('app.meta_description'));
    $ogTitleEn = old('og_title_en', $settings['og_title_en'] ?? __('app.og_title'));
    $ogTitleAm = old('og_title_am', $settings['og_title_am'] ?? __('app.og_title'));
    $ogDescriptionEn = old('og_description_en', $settings['og_description_en'] ?? __('app.og_description'));
    $ogDescriptionAm = old('og_description_am', $settings['og_description_am'] ?? __('app.og_description'));
    $twitterCard = old('twitter_card', $settings['twitter_card'] ?? 'summary_large_image');
    $robots = old('robots', $settings['robots'] ?? 'index,follow,max-image-preview:large');
    $currentImagePath = $settings['og_image'] ?? null;
    $currentImageUrl = $currentImagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($currentImagePath) : asset('images/og-cover.png');
@endphp

<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.seo_settings') }}</h1>
    <p class="text-sm text-muted-text mt-1">{{ __('app.seo_help') }}</p>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6"
     x-data="{
        previewLocale: 'en',
        ogTitleEn: @js($ogTitleEn),
        ogTitleAm: @js($ogTitleAm),
        ogDescriptionEn: @js($ogDescriptionEn),
        ogDescriptionAm: @js($ogDescriptionAm),
        imagePreview: @js($currentImageUrl),
        updateImagePreview(event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => { this.imagePreview = e.target?.result || this.imagePreview; };
            reader.readAsDataURL(file);
        }
     }">
    <form method="POST"
          action="{{ route('admin.seo.update') }}"
          enctype="multipart/form-data"
          class="xl:col-span-2 space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.site_identity') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="site_title_en" class="block text-sm font-medium text-secondary mb-1">{{ __('app.site_title_en') }}</label>
                    <input type="text"
                           id="site_title_en"
                           name="site_title_en"
                           value="{{ $siteTitleEn }}"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label for="site_title_am" class="block text-sm font-medium text-secondary mb-1">{{ __('app.site_title_am') }}</label>
                    <input type="text"
                           id="site_title_am"
                           name="site_title_am"
                           value="{{ $siteTitleAm }}"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.meta_description_label') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="meta_description_en" class="block text-sm font-medium text-secondary mb-1">{{ __('app.meta_description_en') }}</label>
                    <textarea id="meta_description_en"
                              name="meta_description_en"
                              rows="4"
                              maxlength="1000"
                              class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">{{ $metaDescriptionEn }}</textarea>
                </div>
                <div>
                    <label for="meta_description_am" class="block text-sm font-medium text-secondary mb-1">{{ __('app.meta_description_am') }}</label>
                    <textarea id="meta_description_am"
                              name="meta_description_am"
                              rows="4"
                              maxlength="1000"
                              class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">{{ $metaDescriptionAm }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.open_graph') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="og_title_en" class="block text-sm font-medium text-secondary mb-1">{{ __('app.og_title_en') }}</label>
                    <input type="text"
                           id="og_title_en"
                           name="og_title_en"
                           x-model="ogTitleEn"
                           value="{{ $ogTitleEn }}"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label for="og_title_am" class="block text-sm font-medium text-secondary mb-1">{{ __('app.og_title_am') }}</label>
                    <input type="text"
                           id="og_title_am"
                           name="og_title_am"
                           x-model="ogTitleAm"
                           value="{{ $ogTitleAm }}"
                           maxlength="255"
                           class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label for="og_description_en" class="block text-sm font-medium text-secondary mb-1">{{ __('app.og_description_en') }}</label>
                    <textarea id="og_description_en"
                              name="og_description_en"
                              x-model="ogDescriptionEn"
                              rows="4"
                              maxlength="1000"
                              class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">{{ $ogDescriptionEn }}</textarea>
                </div>
                <div>
                    <label for="og_description_am" class="block text-sm font-medium text-secondary mb-1">{{ __('app.og_description_am') }}</label>
                    <textarea id="og_description_am"
                              name="og_description_am"
                              x-model="ogDescriptionAm"
                              rows="4"
                              maxlength="1000"
                              class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">{{ $ogDescriptionAm }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.og_image') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <div>
                    <label for="og_image" class="block text-sm font-medium text-secondary mb-1">{{ __('app.upload_new_image') }}</label>
                    <input type="file"
                           id="og_image"
                           name="og_image"
                           accept="image/*"
                           @change="updateImagePreview($event)"
                           class="block w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-accent file:text-on-accent">
                    <p class="text-xs text-muted-text mt-2">{{ __('app.og_image_hint') }}</p>
                    <label class="mt-3 inline-flex items-center gap-2 text-sm text-secondary">
                        <input type="checkbox" name="remove_og_image" value="1">
                        <span>{{ __('app.remove_og_image') }}</span>
                    </label>
                </div>
                <div>
                    <p class="text-sm font-medium text-secondary mb-2">{{ __('app.current_og_image') }}</p>
                    <img :src="imagePreview"
                         src="{{ $currentImageUrl }}"
                         alt="{{ __('app.og_image') }}"
                         class="w-full max-w-sm h-40 object-cover rounded-lg border border-border bg-muted">
                </div>
            </div>
        </div>

        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.twitter_card') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="twitter_card" value="summary" {{ $twitterCard === 'summary' ? 'checked' : '' }}>
                    <span class="text-sm text-secondary">{{ __('app.twitter_card_summary') }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="twitter_card" value="summary_large_image" {{ $twitterCard === 'summary_large_image' ? 'checked' : '' }}>
                    <span class="text-sm text-secondary">{{ __('app.twitter_card_summary_large_image') }}</span>
                </label>
            </div>
        </div>

        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.robots') }}</h2>
            <label for="robots" class="block text-sm font-medium text-secondary mb-1">{{ __('app.robots_directive') }}</label>
            <input type="text"
                   id="robots"
                   name="robots"
                   value="{{ $robots }}"
                   maxlength="255"
                   placeholder="{{ __('app.robots_placeholder') }}"
                   class="w-full px-3 py-2 border border-border rounded-lg bg-card text-primary focus:ring-2 focus:ring-accent outline-none">
            <p class="text-xs text-muted-text mt-2">{{ __('app.robots_help') }}</p>
        </div>

        <div>
            <button type="submit"
                    class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">
                {{ __('app.save') }}
            </button>
        </div>
    </form>

    <div class="xl:col-span-1">
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border sticky top-20">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h2 class="text-base font-semibold text-primary">{{ __('app.share_preview') }}</h2>
                <div class="flex rounded-lg border border-border overflow-hidden">
                    <button type="button"
                            @click="previewLocale = 'en'"
                            class="px-3 py-1 text-xs transition"
                            :class="previewLocale === 'en' ? 'bg-accent text-on-accent' : 'bg-card text-secondary'">
                        {{ __('app.lang_en') }}
                    </button>
                    <button type="button"
                            @click="previewLocale = 'am'"
                            class="px-3 py-1 text-xs transition"
                            :class="previewLocale === 'am' ? 'bg-accent text-on-accent' : 'bg-card text-secondary'">
                        {{ __('app.lang_am') }}
                    </button>
                </div>
            </div>
            <p class="text-xs text-muted-text mb-4">{{ __('app.social_preview_hint') }}</p>

            <div class="border border-border rounded-xl overflow-hidden bg-surface">
                <img :src="imagePreview"
                     src="{{ $currentImageUrl }}"
                     alt="{{ __('app.share_preview') }}"
                     class="w-full h-36 object-cover bg-muted">
                <div class="p-3">
                    <p class="text-[11px] uppercase tracking-wide text-muted-text">{{ parse_url(config('app.url') ?: url('/'), PHP_URL_HOST) ?? __('app.app_name') }}</p>
                    <h3 class="font-semibold text-sm mt-1 leading-snug"
                        x-text="previewLocale === 'en' ? (ogTitleEn || '{{ addslashes(__('app.og_title')) }}') : (ogTitleAm || '{{ addslashes(__('app.og_title')) }}')"></h3>
                    <p class="text-xs text-secondary mt-1 leading-relaxed"
                       x-text="previewLocale === 'en' ? (ogDescriptionEn || '{{ addslashes(__('app.og_description')) }}') : (ogDescriptionAm || '{{ addslashes(__('app.og_description')) }}')"></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

