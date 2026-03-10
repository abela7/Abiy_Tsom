@extends('layouts.admin')
@section('title', __('app.suggest_content_suggestions'))

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl font-bold leading-tight text-primary sm:text-3xl">{{ __('app.suggest_content_suggestions') }}</h1>
    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
        <a href="{{ route('admin.suggestions.my') }}"
           class="inline-flex w-full items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border active:scale-[0.97] touch-manipulation sm:w-auto">
            {{ __('app.suggest_my_suggestions') }}
        </a>
        <a href="{{ route('suggest') }}" target="_blank" rel="noopener"
           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-on-accent transition hover:bg-accent-hover active:scale-[0.97] touch-manipulation sm:w-auto">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('app.suggest_page_title') }}
        </a>
        @if($counts['all'] > 0)
            <form method="POST" action="{{ route('admin.suggestions.clear-all') }}"
                  onsubmit="return confirm('{{ __('app.suggest_clear_all_confirm') }}')" class="w-full sm:w-auto">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-red-300/40 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-600 transition hover:bg-red-100 active:scale-[0.97] touch-manipulation sm:w-auto">
                    {{ __('app.suggest_clear_all_btn') }}
                </button>
            </form>
        @endif
    </div>
</div>

<div class="mb-5 -mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1 scrollbar-none">
    @foreach(['all' => __('app.suggest_all'), 'pending' => __('app.suggest_status_pending'), 'used' => __('app.suggest_status_used'), 'rejected' => __('app.suggest_status_rejected')] as $key => $label)
        <a href="{{ route('admin.suggestions.index', ['status' => $key]) }}"
           class="shrink-0 rounded-xl border px-3.5 py-2 text-sm font-medium transition touch-manipulation {{ $filter === $key ? 'border-accent bg-accent text-on-accent' : 'border-border bg-card text-secondary hover:border-accent/40 active:bg-muted' }}">
            {{ $label }}
            <span class="ml-0.5 text-xs opacity-70">({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

@if($suggestions->isEmpty())
    <div class="space-y-3 rounded-2xl border border-border bg-card p-10 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-muted">
            <svg class="h-7 w-7 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <p class="text-sm text-muted-text">{{ __('app.suggest_no_suggestions') }}</p>
    </div>
@else
    <div class="space-y-6">
        @foreach($groupedSuggestions as $monthGroup)
            @php
                $monthCount = collect($monthGroup['days'])->sum(
                    fn ($dayGroup) => collect($dayGroup['types'])->sum(fn ($typeGroup) => count($typeGroup['items']))
                );
            @endphp

            <section class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm" x-data="{ openMonth: {{ $loop->first ? 'true' : 'false' }} }">
                <button type="button"
                        @click="openMonth = !openMonth"
                        class="flex w-full items-center justify-between gap-3 bg-muted/70 px-4 py-3 text-left transition hover:bg-muted sm:px-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-text">{{ __('app.date_label') }}</p>
                        <h2 class="text-lg font-bold text-primary sm:text-xl">{{ $monthGroup['label'] }}</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="shrink-0 rounded-full border border-border bg-card px-3 py-1 text-xs font-semibold text-secondary">
                            {{ $monthCount }}
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-muted-text transition-transform" :class="openMonth && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                <div x-show="openMonth" x-cloak x-collapse class="space-y-5 border-t border-border p-4 sm:p-5">
                    @foreach($monthGroup['days'] as $dayGroup)
                        @php
                            $dayCount = collect($dayGroup['types'])->sum(fn ($typeGroup) => count($typeGroup['items']));
                        @endphp

                        <div class="space-y-3 rounded-xl border border-border bg-background p-3 sm:p-4" x-data="{ openDay: {{ $loop->first ? 'true' : 'false' }} }">
                            <button type="button"
                                    @click="openDay = !openDay"
                                    class="flex w-full items-center justify-between gap-3 text-left">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-reflection-bg text-sm font-bold text-accent-secondary">
                                        {{ preg_replace('/\D+/', '', $dayGroup['label']) ?: '?' }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-text">{{ __('app.date_label') }}</p>
                                        <h3 class="text-base font-semibold text-primary sm:text-lg">{{ $dayGroup['label'] }}</h3>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-semibold text-muted-text">
                                        {{ $dayCount }}
                                    </span>
                                    <svg class="h-4 w-4 shrink-0 text-muted-text transition-transform" :class="openDay && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            <div x-show="openDay" x-cloak x-collapse class="space-y-4 border-t border-border pt-3">
                                @foreach($dayGroup['types'] as $typeGroup)
                                    <div class="space-y-2.5 rounded-lg border border-border bg-card" x-data="{ openType: {{ $loop->first ? 'true' : 'false' }} }">
                                        <button type="button"
                                                @click="openType = !openType"
                                                class="flex w-full items-center justify-between gap-3 px-3 py-2.5 text-left transition hover:bg-muted/40">
                                            <h4 class="text-sm font-semibold text-accent">{{ $typeGroup['label'] }}</h4>
                                            <div class="flex items-center gap-3">
                                                <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-semibold text-muted-text">
                                                    {{ count($typeGroup['items']) }}
                                                </span>
                                                <svg class="h-4 w-4 shrink-0 text-muted-text transition-transform" :class="openType && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </div>
                                        </button>

                                        <div x-show="openType" x-cloak x-collapse class="grid gap-3 border-t border-border p-3 xl:grid-cols-2">
                                            @foreach($typeGroup['items'] as $s)
                                                <div class="overflow-hidden rounded-xl border border-border bg-background shadow-sm" x-data="{ open: false }">
                                                    <button type="button"
                                                            @click="open = !open"
                                                            class="flex w-full items-start gap-3 p-3.5 text-left transition hover:bg-muted/40 active:bg-muted/60 sm:p-4">
                                                        <div class="min-w-0 flex-1">
                                                            <div class="mb-1 flex flex-wrap items-center gap-1.5">
                                                                <span class="text-[10px] font-bold uppercase tracking-wider text-muted-text">{{ strtoupper($s->language) }}</span>
                                                                <span class="text-[10px] text-muted-text">|</span>
                                                                <span class="text-[10px] text-muted-text">{{ $s->displayName() }}</span>
                                                                @if($s->user)
                                                                    <span class="text-[10px] text-accent">({{ $s->user->role }})</span>
                                                                @endif
                                                                <span class="ml-auto text-[10px] text-muted-text">{{ $s->created_at->format('M d') }}</span>
                                                            </div>

                                                            <div class="flex items-start gap-3">
                                                                <div class="min-w-0 flex-1">
                                                                    <p class="text-sm font-semibold leading-snug text-primary">{{ $s->title ?: '-' }}</p>
                                                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-muted-text">
                                                                        @if($s->ethiopianDateLabel())
                                                                            <span>{{ $s->ethiopianDateLabel() }}</span>
                                                                        @endif
                                                                        @if($s->entryScopeLabel())
                                                                            <span>{{ $s->entryScopeLabel() }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                @if($s->isUsed())
                                                                    <span class="rounded-full bg-success-bg px-2 py-0.5 text-[10px] font-bold uppercase text-success">{{ __('app.suggest_status_used') }}</span>
                                                                @elseif($s->status === 'rejected')
                                                                    <span class="rounded-full bg-error-bg px-2 py-0.5 text-[10px] font-bold uppercase text-error">{{ __('app.suggest_status_rejected') }}</span>
                                                                @else
                                                                    <span class="rounded-full bg-reflection-bg px-2 py-0.5 text-[10px] font-bold uppercase text-accent-secondary">{{ __('app.suggest_status_pending') }}</span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <svg class="mt-1 h-4 w-4 shrink-0 text-muted-text transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                        </svg>
                                                    </button>

                                                    <div x-show="open" x-cloak x-collapse class="border-t border-border">
                                                        <div class="space-y-2 px-3.5 py-3 sm:px-4">
                                                            @if($s->ethiopianDateLabel())
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Date:</span> <span class="text-secondary">{{ $s->ethiopianDateLabel() }}</span></p>
                                                            @endif
                                                            @if($s->entryScopeLabel())
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Scope:</span> <span class="text-secondary">{{ $s->entryScopeLabel() }}</span></p>
                                                            @endif
                                                            @if($s->content_area === 'synaxarium_celebration')
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Main:</span> <span class="text-secondary">{{ $s->structuredValue('is_main') ? __('app.yes') : __('app.no') }}</span></p>
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Order:</span> <span class="text-secondary">{{ $s->structuredValue('sort_order', 0) }}</span></p>
                                                            @endif
                                                            @if($s->structuredValue('lectionary_section_label'))
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Section:</span> <span class="text-secondary">{{ $s->structuredValue('lectionary_section_label') }}</span></p>
                                                            @endif
                                                            @if($s->structuredValue('resource_type_label'))
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Resource:</span> <span class="text-secondary">{{ $s->structuredValue('resource_type_label') }}</span></p>
                                                            @endif
                                                            @if($s->reference)
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Ref:</span> <span class="text-secondary">{{ $s->reference }}</span></p>
                                                            @endif
                                                            @if($s->author)
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Author:</span> <span class="text-secondary">{{ $s->author }}</span></p>
                                                            @endif
                                                            @if($s->url)
                                                                <p class="text-xs"><span class="font-semibold text-muted-text">Link:</span> <a href="{{ $s->url }}" target="_blank" rel="noopener" class="break-all text-accent hover:underline">{{ $s->url }}</a></p>
                                                            @endif
                                                            @if($s->imageUrl())
                                                                <img src="{{ $s->imageUrl() }}" alt="" class="h-24 w-24 rounded-lg border border-border object-cover">
                                                            @endif
                                                            @if($s->content_detail)
                                                                <p class="whitespace-pre-wrap text-xs leading-relaxed text-secondary">{{ $s->content_detail }}</p>
                                                            @endif
                                                            @if($s->notes)
                                                                <p class="text-xs italic text-muted-text">{{ $s->notes }}</p>
                                                            @endif

                                                            @if($s->structured_payload)
                                                                @foreach(['en' => 'English', 'am' => 'Amharic'] as $lang => $label)
                                                                    @php
                                                                        $hasLang = $s->structuredValue("title_{$lang}")
                                                                            || $s->structuredValue("reference_{$lang}")
                                                                            || $s->structuredValue("url_{$lang}")
                                                                            || $s->structuredValue("text_{$lang}")
                                                                            || $s->structuredValue("content_detail_{$lang}")
                                                                            || $s->structuredValue("lyrics_{$lang}");
                                                                    @endphp
                                                                    @if($hasLang)
                                                                        <div class="mt-2 border-l-2 border-accent/30 pl-2">
                                                                            <p class="text-[10px] font-semibold text-muted-text">{{ $label }}</p>
                                                                            @if($s->structuredValue("reference_{$lang}"))<p class="text-xs"><span class="font-semibold text-muted-text">Ref:</span> {{ $s->structuredValue("reference_{$lang}") }}</p>@endif
                                                                            @if($s->structuredValue("title_{$lang}"))<p class="text-xs"><span class="font-semibold text-muted-text">Title:</span> {{ $s->structuredValue("title_{$lang}") }}</p>@endif
                                                                            @if($s->structuredValue("summary_{$lang}"))<p class="text-xs"><span class="font-semibold text-muted-text">Summary:</span> {{ $s->structuredValue("summary_{$lang}") }}</p>@endif
                                                                            @if($s->structuredValue("url_{$lang}"))<p class="text-xs"><span class="font-semibold text-muted-text">Link:</span> <a href="{{ $s->structuredValue("url_{$lang}") }}" target="_blank" rel="noopener" class="break-all text-accent hover:underline">{{ $s->structuredValue("url_{$lang}") }}</a></p>@endif
                                                                            @if($s->structuredValue("text_{$lang}"))<p class="whitespace-pre-wrap text-xs leading-relaxed text-secondary"><span class="font-semibold text-muted-text">Text:</span> {{ $s->structuredValue("text_{$lang}") }}</p>@endif
                                                                            @if($s->structuredValue("content_detail_{$lang}"))<p class="whitespace-pre-wrap text-xs leading-relaxed text-secondary">{{ $s->structuredValue("content_detail_{$lang}") }}</p>@endif
                                                                            @if($s->structuredValue("lyrics_{$lang}"))<p class="whitespace-pre-wrap text-xs leading-relaxed text-secondary"><span class="font-semibold text-muted-text">Lyrics:</span> {{ $s->structuredValue("lyrics_{$lang}") }}</p>@endif
                                                                        </div>
                                                                    @endif
                                                                @endforeach

                                                                @if($s->structuredValue('sinksar_images'))
                                                                    <div class="space-y-2 pt-1">
                                                                        <p class="text-[10px] font-semibold text-muted-text">Images</p>
                                                                        <div class="grid gap-2 sm:grid-cols-2">
                                                                            @foreach((array) $s->structuredValue('sinksar_images', []) as $img)
                                                                                @php
                                                                                    $path = is_array($img) ? ($img['path'] ?? null) : null;
                                                                                    $url = $path ? url(\Illuminate\Support\Facades\Storage::disk('public')->url($path)) : null;
                                                                                @endphp
                                                                                @if($url)
                                                                                    <div class="rounded-lg border border-border p-2">
                                                                                        <img src="{{ $url }}" alt="" class="h-24 w-full rounded border border-border object-cover">
                                                                                        @if(!empty($img['caption_am']))<p class="mt-1 text-[11px] text-secondary">AM: {{ $img['caption_am'] }}</p>@endif
                                                                                        @if(!empty($img['caption_en']))<p class="text-[11px] text-secondary">EN: {{ $img['caption_en'] }}</p>@endif
                                                                                    </div>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>

                                                        @if($s->isUsed() && $s->usedBy)
                                                            <div class="px-3.5 pb-1 sm:px-4">
                                                                <div class="flex items-center gap-1.5 text-xs font-medium text-success">
                                                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                                    </svg>
                                                                    <span>{{ __('app.suggest_used_by', ['name' => $s->usedBy->name, 'date' => $s->used_at->format('M d')]) }}</span>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        <div class="flex flex-wrap gap-2 px-3.5 py-3 sm:px-4">
                                                            @if($s->isUsed())
                                                                <form method="POST" action="{{ route('admin.suggestions.unmark-used', $s) }}" class="min-w-[12rem] flex-1">
                                                                    @csrf
                                                                    <button type="submit" class="h-10 w-full rounded-xl border border-border text-sm font-medium text-muted-text transition hover:bg-muted active:scale-[0.97]">
                                                                        {{ __('app.suggest_unmark_used') }}
                                                                    </button>
                                                                </form>
                                                            @else
                                                                @if(($s->content_area === 'synaxarium_celebration' && $s->ethiopian_day) || ($s->ethiopian_month && $s->ethiopian_day))
                                                                    <form method="POST" action="{{ route('admin.suggestions.apply', $s) }}" class="min-w-[12rem] flex-1"
                                                                          onsubmit="return confirm('{{ __('app.suggest_apply_confirm') }}')">
                                                                        @csrf
                                                                        <button type="submit" class="flex h-10 w-full items-center justify-center gap-1.5 rounded-xl bg-success-bg px-4 text-sm font-bold text-success transition hover:bg-green-100 active:scale-[0.97]">
                                                                            {{ __('app.suggest_apply_btn') }}
                                                                            <span class="text-xs font-normal opacity-70">({{ $s->ethiopianDateLabel() }})</span>
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                                <form method="POST" action="{{ route('admin.suggestions.mark-used', $s) }}" class="min-w-[12rem] flex-1">
                                                                    @csrf
                                                                    <button type="submit" class="flex h-10 w-full items-center justify-center gap-1.5 rounded-xl bg-accent px-4 text-sm font-bold text-on-accent transition hover:bg-accent-hover active:scale-[0.97]">
                                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                                        </svg>
                                                                        {{ __('app.suggest_mark_used') }}
                                                                    </button>
                                                                </form>
                                                                @if($s->status !== 'rejected')
                                                                    <form method="POST" action="{{ route('admin.suggestions.reject', $s) }}">
                                                                        @csrf
                                                                        <button type="submit" class="h-10 rounded-xl border border-border px-4 text-sm font-medium text-error transition hover:bg-error-bg active:scale-[0.97]">
                                                                            {{ __('app.suggest_reject') }}
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endif
@endsection
