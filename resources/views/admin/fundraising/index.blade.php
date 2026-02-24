@extends('layouts.admin')

@section('title', __('app.fundraising'))

@section('content')
<div class="max-w-3xl">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-primary">{{ __('app.fundraising') }}</h1>
        @if($campaign && $campaign->is_active)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                {{ __('app.fundraising_active') }}
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-muted text-muted-text">
                <span class="w-1.5 h-1.5 rounded-full bg-muted-text"></span>
                {{ __('app.fundraising_inactive') }}
            </span>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Campaign Settings --}}
    <div class="bg-card rounded-2xl border border-border shadow-sm p-6 mb-6" x-data="{ lang: 'en' }">

        <h2 class="text-base font-semibold text-primary mb-4">{{ __('app.fundraising_settings') }}</h2>

        <form method="POST" action="/admin/fundraising">
            @csrf

            <div class="space-y-5">

                {{-- Active toggle --}}
                <div class="flex items-center justify-between py-3 border-b border-border">
                    <div>
                        <p class="text-sm font-medium text-primary">{{ __('app.fundraising_show_popup') }}</p>
                        <p class="text-xs text-muted-text mt-0.5">{{ __('app.fundraising_show_popup_desc') }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                               {{ $campaign?->is_active ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-muted peer-focus:outline-none rounded-full peer
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:bg-accent"></div>
                    </label>
                </div>

                {{-- YouTube URL (shared) --}}
                <div>
                    <label class="block text-sm font-medium text-primary mb-1.5">{{ __('app.fundraising_youtube_url') }}</label>
                    <input type="url" name="youtube_url"
                           value="{{ old('youtube_url', $campaign?->youtube_url) }}"
                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                           placeholder="https://www.youtube.com/watch?v=...">
                    <p class="mt-1 text-xs text-muted-text">{{ __('app.fundraising_youtube_hint') }}</p>
                    @error('youtube_url')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Donate URL (shared) --}}
                <div>
                    <label class="block text-sm font-medium text-primary mb-1.5">{{ __('app.fundraising_donate_url') }}</label>
                    <input type="url" name="donate_url"
                           value="{{ old('donate_url', $campaign?->donate_url ?? 'https://donate.abuneteklehaymanot.org/') }}"
                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                    @error('donate_url')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Language tabs for title + description --}}
                <div class="border border-border rounded-2xl overflow-hidden">

                    {{-- Tab bar --}}
                    <div class="flex border-b border-border bg-muted">
                        <button type="button"
                                @click="lang = 'en'"
                                :class="lang === 'en'
                                    ? 'border-b-2 border-accent text-accent bg-card font-semibold'
                                    : 'text-muted-text hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm transition">
                            🇬🇧 English
                        </button>
                        <button type="button"
                                @click="lang = 'am'"
                                :class="lang === 'am'
                                    ? 'border-b-2 border-accent text-accent bg-card font-semibold'
                                    : 'text-muted-text hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm transition">
                            🇪🇹 አማርኛ
                        </button>
                    </div>

                    {{-- English fields --}}
                    <div x-show="lang === 'en'" class="p-4 space-y-4 bg-card">
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.fundraising_title') }} <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="title"
                                   value="{{ old('title', $campaign?->title ?? 'Help Us Buy Our Church Building') }}"
                                   class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                   placeholder="Help Us Buy Our Church Building"
                                   required>
                            @error('title')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.fundraising_description') }}
                            </label>
                            <textarea name="description" rows="5"
                                      class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none"
                                      placeholder="We are working towards owning a church building — a place where we will leave a lasting legacy for generations…">{{ old('description', $campaign?->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Amharic fields --}}
                    <div x-show="lang === 'am'" x-cloak class="p-4 space-y-4 bg-card">
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.fundraising_title') }} (አማርኛ)
                                <span class="ml-1 text-muted-text font-normal normal-case">{{ __('app.optional') }}</span>
                            </label>
                            <input type="text" name="title_am"
                                   value="{{ old('title_am', $campaign?->title_am) }}"
                                   class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                   placeholder="የሊቨርፑል ቤተ ክርስቲያን ሕልም እውን እንዲሆን ያግዙን">
                            @error('title_am')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                                {{ __('app.fundraising_description') }} (አማርኛ)
                                <span class="ml-1 text-muted-text font-normal normal-case">{{ __('app.optional') }}</span>
                            </label>
                            <textarea name="description_am" rows="5"
                                      class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none"
                                      placeholder="ለትውልድ የሚሻገር ትልቅ አሻራ የምናሳርፍበት…">{{ old('description_am', $campaign?->description_am) }}</textarea>
                            @error('description_am')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <p class="text-xs text-muted-text">
                            {{ __('app.fundraising_am_fallback_note') }}
                        </p>
                    </div>

                </div>

            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                        class="px-5 py-2.5 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                    {{ __('app.save_changes') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Leads / Interested Members --}}
    @if($stats)
    <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-primary">{{ __('app.fundraising_responses') }}</h2>
            @if($stats['interested'] > 0 || $stats['snoozed'] > 0)
                <form method="POST" action="/admin/fundraising/reset"
                      onsubmit="return confirm('{{ __('app.fundraising_reset_confirm') }}')">
                    @csrf
                    <button type="submit"
                            class="text-xs font-medium text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 transition px-3 py-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                        {{ __('app.fundraising_reset_all') }}
                    </button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4 text-center">
                <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $stats['interested'] }}</p>
                <p class="text-xs text-green-600 dark:text-green-500 mt-0.5">{{ __('app.fundraising_interested') }}</p>
            </div>
            <div class="rounded-xl bg-muted border border-border p-4 text-center">
                <p class="text-2xl font-bold text-primary">{{ $stats['snoozed'] }}</p>
                <p class="text-xs text-muted-text mt-0.5">{{ __('app.fundraising_snoozed') }}</p>
            </div>
        </div>

        @if($stats['leads']->isNotEmpty())
            <h3 class="text-sm font-semibold text-primary mb-3">{{ __('app.fundraising_contact_list') }}</h3>
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted text-muted-text text-xs uppercase">
                        <tr>
                            <th class="px-4 py-2.5 text-left">{{ __('app.name') }}</th>
                            <th class="px-4 py-2.5 text-left">{{ __('app.phone') }}</th>
                            <th class="px-4 py-2.5 text-left">{{ __('app.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($stats['leads'] as $lead)
                            <tr class="hover:bg-muted/50 transition">
                                <td class="px-4 py-3 font-medium text-primary">{{ $lead->contact_name }}</td>
                                <td class="px-4 py-3 text-secondary">
                                    <a href="tel:{{ $lead->contact_phone }}" class="text-accent hover:underline">
                                        {{ $lead->contact_phone }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-muted-text">{{ $lead->interested_at?->format('M j, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-muted-text">{{ __('app.fundraising_no_leads_yet') }}</p>
        @endif
    </div>
    @endif

</div>
@endsection
