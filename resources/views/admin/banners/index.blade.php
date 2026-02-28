@extends('layouts.admin')

@section('title', __('app.banner_admin_title'))

@section('content')
<div class="max-w-3xl">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-primary">{{ __('app.banner_admin_title') }}</h1>
    </div>

    @if(session('success'))
        <div class="mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Create / Edit Form --}}
    <div class="bg-card rounded-2xl border border-border shadow-sm p-6 mb-6" x-data="{ lang: 'en' }">

        <h2 class="text-base font-semibold text-primary mb-4">
            {{ $editing ? __('app.banner_admin_edit') : __('app.banner_admin_create') }}
        </h2>

        <form method="POST"
              action="{{ $editing ? '/admin/banners/'.$editing->id : '/admin/banners' }}"
              enctype="multipart/form-data">
            @csrf
            @if($editing)
                @method('PUT')
            @endif

            <div class="space-y-5">

                {{-- Active toggle --}}
                <div class="flex items-center justify-between py-3 border-b border-border">
                    <div>
                        <p class="text-sm font-medium text-primary">{{ __('app.active') }}</p>
                        <p class="text-xs text-muted-text mt-0.5">{{ __('app.banner_admin_active_desc') }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                               {{ old('is_active', $editing?->is_active) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-muted peer-focus:outline-none rounded-full peer
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:bg-accent"></div>
                    </label>
                </div>

                {{-- Image upload --}}
                <div>
                    <label class="block text-sm font-medium text-primary mb-1.5">{{ __('app.banner_image') }}</label>
                    @if($editing?->image)
                        <div class="mb-2 flex items-end gap-3">
                            <img src="{{ $editing->imageUrl() }}" alt="" class="h-24 rounded-xl object-cover">
                            <label class="inline-flex items-center gap-2 text-sm text-red-500 hover:text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-border text-red-500 focus:ring-red-400">
                                {{ __('app.remove') }}
                            </label>
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*"
                           class="w-full text-sm text-muted-text file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 transition">
                    @error('image')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sort order --}}
                <div>
                    <label class="block text-sm font-medium text-primary mb-1.5">{{ __('app.sort_order') }}</label>
                    <input type="number" name="sort_order" min="0" max="65535"
                           value="{{ old('sort_order', $editing?->sort_order ?? 0) }}"
                           class="w-24 px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                </div>

                {{-- Button URL (shared) --}}
                <div>
                    <label class="block text-sm font-medium text-primary mb-1.5">{{ __('app.button_url') }}</label>
                    <input type="url" name="button_url"
                           value="{{ old('button_url', $editing?->button_url) }}"
                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                           placeholder="https://...">
                    <p class="mt-1 text-xs text-muted-text">{{ __('app.banner_url_hint') }}</p>
                    @error('button_url')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Language tabs --}}
                <div class="border border-border rounded-2xl overflow-hidden">
                    <div class="flex border-b border-border bg-muted">
                        <button type="button"
                                @click="lang = 'en'"
                                :class="lang === 'en'
                                    ? 'border-b-2 border-accent text-accent bg-card font-semibold'
                                    : 'text-muted-text hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm transition">
                            English
                        </button>
                        <button type="button"
                                @click="lang = 'am'"
                                :class="lang === 'am'
                                    ? 'border-b-2 border-accent text-accent bg-card font-semibold'
                                    : 'text-muted-text hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm transition">
                            አማርኛ
                        </button>
                    </div>

                    {{-- English fields --}}
                    <div x-show="lang === 'en'" class="p-4 space-y-4 bg-card">
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.title') }} <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="title"
                                   value="{{ old('title', $editing?->title) }}"
                                   class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                   placeholder="Banner title" required>
                            @error('title')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.description_label') }}
                            </label>
                            <textarea name="description" rows="4"
                                      class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none"
                                      placeholder="Banner description...">{{ old('description', $editing?->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.button_label') }}
                            </label>
                            <input type="text" name="button_label"
                                   value="{{ old('button_label', $editing?->button_label ?? "I'm Interested") }}"
                                   class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                   placeholder="I'm Interested">
                        </div>
                    </div>

                    {{-- Amharic fields --}}
                    <div x-show="lang === 'am'" x-cloak class="p-4 space-y-4 bg-card">
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.title') }} (አማርኛ)
                                <span class="ml-1 text-muted-text font-normal normal-case">{{ __('app.optional') }}</span>
                            </label>
                            <input type="text" name="title_am"
                                   value="{{ old('title_am', $editing?->title_am) }}"
                                   class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                            @error('title_am')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.description_label') }} (አማርኛ)
                                <span class="ml-1 text-muted-text font-normal normal-case">{{ __('app.optional') }}</span>
                            </label>
                            <textarea name="description_am" rows="4"
                                      class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none">{{ old('description_am', $editing?->description_am) }}</textarea>
                            @error('description_am')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.button_label') }} (አማርኛ)
                                <span class="ml-1 text-muted-text font-normal normal-case">{{ __('app.optional') }}</span>
                            </label>
                            <input type="text" name="button_label_am"
                                   value="{{ old('button_label_am', $editing?->button_label_am) }}"
                                   class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                        </div>
                        <p class="text-xs text-muted-text">
                            {{ __('app.fundraising_am_fallback_note') }}
                        </p>
                    </div>
                </div>

            </div>

            <div class="mt-6 flex items-center gap-3 justify-end">
                @if($editing)
                    <a href="/admin/banners"
                       class="px-4 py-2.5 text-sm font-medium text-muted-text hover:text-primary transition">
                        {{ __('app.cancel') }}
                    </a>
                @endif
                <button type="submit"
                        class="px-5 py-2.5 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                    {{ $editing ? __('app.save_changes') : __('app.banner_admin_create') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Banners list --}}
    @if($banners->isNotEmpty())
    <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
        <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.banner_admin_title') }}</h2>

        <div class="space-y-3">
            @foreach($banners as $banner)
            <div class="flex items-start gap-4 p-4 rounded-xl border border-border bg-surface">
                @if($banner->image)
                    <img src="{{ $banner->imageUrl() }}" alt="" class="w-20 h-14 rounded-lg object-cover shrink-0">
                @endif
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <p class="font-medium text-primary text-sm truncate">{{ $banner->title }}</p>
                        @if($banner->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                {{ __('app.active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-text">
                                {{ __('app.inactive') }}
                            </span>
                        @endif
                    </div>
                    @if($banner->description)
                        <p class="text-xs text-muted-text line-clamp-2">{{ $banner->description }}</p>
                    @endif
                    <p class="text-xs text-muted-text mt-1">
                        {{ __('app.banner_admin_responses') }}: <span class="font-semibold text-primary">{{ $banner->responses()->count() }}</span>
                        &middot; {{ __('app.sort_order') }}: {{ $banner->sort_order }}
                    </p>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <form method="POST" action="/admin/banners/{{ $banner->id }}/toggle" class="inline">
                        @csrf
                        <button type="submit" class="p-2 rounded-lg hover:bg-muted transition text-muted-text hover:text-primary"
                                title="{{ $banner->is_active ? __('app.inactive') : __('app.active') }}">
                            @if($banner->is_active)
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            @endif
                        </button>
                    </form>
                    <a href="/admin/banners?edit={{ $banner->id }}"
                       class="p-2 rounded-lg hover:bg-muted transition text-muted-text hover:text-accent"
                       title="{{ __('app.edit') }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <form method="POST" action="/admin/banners/{{ $banner->id }}"
                          onsubmit="return confirm('{{ __('app.banner_admin_delete_confirm') }}')"
                          class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-muted-text hover:text-red-500"
                                title="{{ __('app.delete') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Responses for this banner --}}
            @if($banner->responses()->count() > 0)
            <div class="ml-4 mb-2">
                <details class="group">
                    <summary class="cursor-pointer text-xs font-medium text-accent hover:underline">
                        {{ __('app.banner_admin_responses') }} ({{ $banner->responses()->count() }})
                    </summary>
                    <div class="mt-2 overflow-x-auto rounded-xl border border-border">
                        <table class="w-full text-sm">
                            <thead class="bg-muted text-muted-text text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">{{ __('app.name') }}</th>
                                    <th class="px-4 py-2.5 text-left">{{ __('app.banner_phone_placeholder') }}</th>
                                    <th class="px-4 py-2.5 text-left">{{ __('app.created') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach($banner->responses()->with('member:id,baptism_name')->latest()->get() as $resp)
                                <tr class="hover:bg-muted/50 transition">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-primary text-sm">{{ $resp->contact_name }}</p>
                                        @if($resp->member)
                                            <p class="text-xs text-muted-text">{{ $resp->member->baptism_name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="tel:{{ $resp->contact_phone }}" class="text-accent hover:underline text-sm">{{ $resp->contact_phone }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted-text">{{ $resp->created_at->format('M j, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
