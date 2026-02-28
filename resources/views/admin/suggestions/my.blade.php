@extends('layouts.admin')
@section('title', __('app.suggest_my_suggestions'))

@section('content')
<div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.suggest_my_suggestions') }}</h1>
    <a href="{{ route('suggest') }}"
       class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-on-accent transition hover:bg-accent-hover active:scale-[0.97] touch-manipulation sm:w-auto">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        {{ __('app.suggest_page_title') }}
    </a>
</div>

@if($suggestions->isEmpty())
    <div class="bg-card rounded-2xl border border-border p-10 text-center space-y-3">
        <div class="mx-auto w-14 h-14 rounded-full bg-muted flex items-center justify-center">
            <svg class="w-7 h-7 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <p class="text-sm text-muted-text">{{ __('app.suggest_no_suggestions') }}</p>
        <a href="{{ route('suggest') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-accent hover:underline">
            {{ __('app.suggest_page_title') }}
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </a>
    </div>
@else
    @php
        $grouped = $suggestions->groupBy(function ($s) {
            if ($s->isUsed()) return 'used';
            if ($s->status === 'rejected') return 'rejected';
            return 'pending';
        });
        $sections = [
            'pending'  => ['label' => __('app.suggest_status_pending'),  'color' => 'accent-secondary', 'bg' => 'reflection-bg'],
            'used'     => ['label' => __('app.suggest_status_used'),     'color' => 'success',          'bg' => 'success-bg'],
            'rejected' => ['label' => __('app.suggest_status_rejected'), 'color' => 'error',            'bg' => 'error-bg'],
        ];
    @endphp

    {{-- Quick stats --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-3 mb-5">
        @foreach($sections as $key => $sec)
            @php $count = ($grouped[$key] ?? collect())->count(); @endphp
            <div class="bg-card rounded-xl border border-border p-3 sm:p-4 text-center">
                <p class="text-xl sm:text-2xl font-black text-{{ $sec['color'] }}">{{ $count }}</p>
                <p class="text-[10px] sm:text-xs font-bold text-muted-text uppercase tracking-widest mt-0.5">{{ $sec['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Grouped cards --}}
    @foreach($sections as $key => $sec)
        @if(($grouped[$key] ?? collect())->isNotEmpty())
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-{{ $sec['color'] }}"></span>
                    <h2 class="text-xs font-bold text-muted-text uppercase tracking-widest">
                        {{ $sec['label'] }} ({{ ($grouped[$key])->count() }})
                    </h2>
                </div>

                <div class="space-y-2.5">
                    @foreach($grouped[$key] as $s)
                        <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden" x-data="{ open: false }">
                            {{-- Tappable card header --}}
                            <button type="button" @click="open = !open"
                                    class="w-full text-left p-3.5 sm:p-4 flex items-start gap-3 active:bg-muted/30 transition touch-manipulation">
                                {{-- Type icon --}}
                                <span class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-{{ $sec['bg'] }} flex items-center justify-center mt-0.5">
                                    @switch($s->type)
                                        @case('bible')
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-{{ $sec['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            @break
                                        @case('mezmur')
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-{{ $sec['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                            @break
                                        @case('sinksar')
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-{{ $sec['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            @break
                                        @case('book')
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-{{ $sec['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                            @break
                                        @default
                                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-{{ $sec['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    @endswitch
                                </span>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-[10px] font-bold text-{{ $sec['color'] }} uppercase tracking-wider">{{ $s->typeLabel() }}</span>
                                        <span class="text-[10px] font-bold text-muted-text uppercase">{{ $s->language }}</span>
                                    </div>
                                    <p class="text-sm font-semibold text-primary leading-snug truncate">{{ $s->title ?: '-' }}</p>
                                    <p class="text-[11px] text-muted-text mt-0.5">{{ $s->created_at->format('M d, Y · H:i') }}</p>
                                </div>

                                {{-- Expand arrow --}}
                                <svg class="w-4 h-4 text-muted-text shrink-0 mt-1 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Expanded detail --}}
                            <div x-show="open" x-cloak x-collapse>
                                <div class="px-3.5 pb-3.5 sm:px-4 sm:pb-4 space-y-2 border-t border-border pt-3 ml-12 sm:ml-[52px]">
                                    @if($s->reference)
                                        <div class="text-xs">
                                            <span class="font-semibold text-muted-text">{{ __('app.suggest_reference_label') }}:</span>
                                            <span class="text-secondary ml-1">{{ $s->reference }}</span>
                                        </div>
                                    @endif
                                    @if($s->author)
                                        <div class="text-xs">
                                            <span class="font-semibold text-muted-text">{{ __('app.suggest_author_label') }}:</span>
                                            <span class="text-secondary ml-1">{{ $s->author }}</span>
                                        </div>
                                    @endif
                                    @if($s->url)
                                        <div class="text-xs">
                                            <span class="font-semibold text-muted-text">Link:</span>
                                            <a href="{{ $s->url }}" target="_blank" rel="noopener" class="text-accent hover:underline break-all ml-1">{{ $s->url }}</a>
                                        </div>
                                    @endif
                                    @if($s->content_detail)
                                        <p class="text-xs text-secondary whitespace-pre-wrap leading-relaxed">{{ $s->content_detail }}</p>
                                    @endif
                                    @if($s->notes)
                                        <p class="text-xs text-muted-text italic">{{ $s->notes }}</p>
                                    @endif
                                    @if($s->isUsed() && $s->usedBy)
                                        <div class="flex items-center gap-1.5 pt-1">
                                            <svg class="w-3.5 h-3.5 text-success shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span class="text-xs text-success font-medium">
                                                {{ __('app.suggest_used_by', ['name' => $s->usedBy->name, 'date' => $s->used_at->format('M d, Y')]) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
@endif
@endsection
