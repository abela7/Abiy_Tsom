@extends('layouts.admin')
@section('title', __('app.suggest_content_suggestions'))

@section('content')
<div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.suggest_content_suggestions') }}</h1>
    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
        <a href="{{ route('admin.suggestions.my') }}"
           class="inline-flex w-full items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border active:scale-[0.97] touch-manipulation sm:w-auto">
            {{ __('app.suggest_my_suggestions') }}
        </a>
        <a href="{{ route('suggest') }}" target="_blank" rel="noopener"
           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-on-accent transition hover:bg-accent-hover active:scale-[0.97] touch-manipulation sm:w-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('app.suggest_page_title') }}
        </a>
    </div>
</div>

{{-- Filter tabs — horizontal scroll on mobile --}}
<div class="flex gap-1.5 overflow-x-auto pb-1 mb-5 scrollbar-none -mx-1 px-1">
    @foreach(['all' => __('app.suggest_all'), 'pending' => __('app.suggest_status_pending'), 'used' => __('app.suggest_status_used'), 'rejected' => __('app.suggest_status_rejected')] as $key => $label)
        <a href="{{ route('admin.suggestions.index', ['status' => $key]) }}"
           class="shrink-0 px-3.5 py-2 rounded-xl text-sm font-medium border transition touch-manipulation {{ $filter === $key ? 'bg-accent text-on-accent border-accent' : 'bg-card text-secondary border-border hover:border-accent/40 active:bg-muted' }}">
            {{ $label }}
            <span class="ml-0.5 text-xs opacity-70">({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

@if($suggestions->isEmpty())
    <div class="bg-card rounded-2xl border border-border p-10 text-center space-y-3">
        <div class="mx-auto w-14 h-14 rounded-full bg-muted flex items-center justify-center">
            <svg class="w-7 h-7 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
            </svg>
        </div>
        <p class="text-sm text-muted-text">{{ __('app.suggest_no_suggestions') }}</p>
    </div>
@else
    {{-- ═══ Desktop table (hidden on mobile) ═══ --}}
    <div class="hidden lg:block bg-card rounded-xl shadow-sm border border-border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-secondary w-28">{{ __('app.suggest_type_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.suggest_title_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary w-20">{{ __('app.suggest_language_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary w-36">{{ __('app.suggest_submitter_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary w-28">{{ __('app.status') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary w-24">{{ __('app.date_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary w-36">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach($suggestions as $s)
                    <tr class="hover:bg-muted/50 transition-colors group" x-data="{ expanded: false }">
                        <td class="px-4 py-3 font-medium text-accent text-xs">{{ $s->typeLabel() }}</td>
                        <td class="px-4 py-3">
                            <button type="button" @click="expanded = !expanded" class="text-left hover:text-accent transition">
                                <span class="font-medium text-primary">{{ $s->title ?: '-' }}</span>
                                @if($s->content_detail || $s->reference || $s->author || $s->notes)
                                    <svg class="w-3.5 h-3.5 inline ml-1 text-muted-text transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                @endif
                            </button>
                            <div x-show="expanded" x-cloak x-transition class="mt-2 space-y-1 text-xs text-secondary max-w-md">
                                @if($s->reference)<p><span class="font-semibold text-muted-text">Ref:</span> {{ $s->reference }}</p>@endif
                                @if($s->author)<p><span class="font-semibold text-muted-text">Author:</span> {{ $s->author }}</p>@endif
                                @if($s->content_detail)<p class="whitespace-pre-wrap leading-relaxed">{{ $s->content_detail }}</p>@endif
                                @if($s->notes)<p class="text-muted-text italic">{{ $s->notes }}</p>@endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-secondary uppercase text-xs font-bold">{{ $s->language }}</td>
                        <td class="px-4 py-3 text-secondary text-xs">
                            {{ $s->displayName() }}
                            @if($s->user)
                                <span class="text-accent font-medium">({{ $s->user->role }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($s->isUsed())
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success">{{ __('app.suggest_status_used') }}</span>
                                <p class="text-[10px] text-muted-text mt-0.5">{{ $s->usedBy?->name ?? '?' }} · {{ $s->used_at->format('M d') }}</p>
                            @elseif($s->status === 'rejected')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-error-bg text-error">{{ __('app.suggest_status_rejected') }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-reflection-bg text-accent-secondary">{{ __('app.suggest_status_pending') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-muted-text">{{ $s->created_at->format('M d') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                @if($s->isUsed())
                                    <form method="POST" action="{{ route('admin.suggestions.unmark-used', $s) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-medium border border-border text-muted-text hover:bg-muted hover:text-primary transition">{{ __('app.suggest_unmark_used') }}</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.suggestions.mark-used', $s) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-accent text-on-accent hover:bg-accent-hover transition">{{ __('app.suggest_mark_used') }}</button>
                                    </form>
                                    @if($s->status !== 'rejected')
                                        <form method="POST" action="{{ route('admin.suggestions.reject', $s) }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-medium border border-border text-error hover:bg-error-bg transition">{{ __('app.suggest_reject') }}</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ═══ Mobile/Tablet card list (hidden on desktop) ═══ --}}
    <div class="lg:hidden space-y-2.5">
        @foreach($suggestions as $s)
            <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden" x-data="{ open: false }">
                {{-- Tappable card header --}}
                <button type="button" @click="open = !open"
                        class="w-full text-left p-3.5 sm:p-4 flex items-start gap-3 active:bg-muted/30 transition touch-manipulation">
                    {{-- Type icon with status color --}}
                    @php
                        $statusColor = $s->isUsed() ? 'success' : ($s->status === 'rejected' ? 'error' : 'accent-secondary');
                        $statusBg = $s->isUsed() ? 'success-bg' : ($s->status === 'rejected' ? 'error-bg' : 'reflection-bg');
                    @endphp
                    <span class="shrink-0 w-10 h-10 rounded-xl bg-{{ $statusBg }} flex items-center justify-center">
                        @switch($s->type)
                            @case('bible')
                                <svg class="w-5 h-5 text-{{ $statusColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                @break
                            @case('mezmur')
                                <svg class="w-5 h-5 text-{{ $statusColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                                @break
                            @case('sinksar')
                                <svg class="w-5 h-5 text-{{ $statusColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                @break
                            @case('book')
                                <svg class="w-5 h-5 text-{{ $statusColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                @break
                            @default
                                <svg class="w-5 h-5 text-{{ $statusColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        @endswitch
                    </span>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap mb-0.5">
                            <span class="text-[10px] font-bold text-accent uppercase tracking-wider">{{ $s->typeLabel() }}</span>
                            <span class="text-[10px] text-muted-text">·</span>
                            <span class="text-[10px] font-bold text-muted-text uppercase">{{ $s->language }}</span>
                            @if($s->isUsed())
                                <span class="ml-auto px-1.5 py-0.5 rounded text-[9px] font-bold bg-success-bg text-success uppercase">{{ __('app.suggest_status_used') }}</span>
                            @elseif($s->status === 'rejected')
                                <span class="ml-auto px-1.5 py-0.5 rounded text-[9px] font-bold bg-error-bg text-error uppercase">{{ __('app.suggest_status_rejected') }}</span>
                            @else
                                <span class="ml-auto px-1.5 py-0.5 rounded text-[9px] font-bold bg-reflection-bg text-accent-secondary uppercase">{{ __('app.suggest_status_pending') }}</span>
                            @endif
                        </div>
                        <p class="text-sm font-semibold text-primary leading-snug line-clamp-1">{{ $s->title ?: '-' }}</p>
                        <div class="flex items-center gap-2 mt-0.5 text-[11px] text-muted-text">
                            <span>{{ $s->displayName() }}</span>
                            @if($s->user)
                                <span class="text-accent">({{ $s->user->role }})</span>
                            @endif
                            <span class="ml-auto">{{ $s->created_at->format('M d') }}</span>
                        </div>
                    </div>

                    {{-- Expand arrow --}}
                    <svg class="w-4 h-4 text-muted-text shrink-0 mt-1 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Expanded detail + actions --}}
                <div x-show="open" x-cloak x-collapse>
                    {{-- Content details --}}
                    @if($s->reference || $s->author || $s->content_detail || $s->notes)
                        <div class="px-3.5 sm:px-4 space-y-1.5 border-t border-border pt-3 ml-[52px]">
                            @if($s->reference)
                                <p class="text-xs"><span class="font-semibold text-muted-text">Ref:</span> <span class="text-secondary">{{ $s->reference }}</span></p>
                            @endif
                            @if($s->author)
                                <p class="text-xs"><span class="font-semibold text-muted-text">Author:</span> <span class="text-secondary">{{ $s->author }}</span></p>
                            @endif
                            @if($s->content_detail)
                                <p class="text-xs text-secondary whitespace-pre-wrap leading-relaxed">{{ $s->content_detail }}</p>
                            @endif
                            @if($s->notes)
                                <p class="text-xs text-muted-text italic">{{ $s->notes }}</p>
                            @endif
                        </div>
                    @endif

                    @if($s->isUsed() && $s->usedBy)
                        <div class="flex items-center gap-1.5 px-3.5 sm:px-4 pt-2 ml-[52px]">
                            <svg class="w-3.5 h-3.5 text-success shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-xs text-success font-medium">
                                {{ __('app.suggest_used_by', ['name' => $s->usedBy->name, 'date' => $s->used_at->format('M d')]) }}
                            </span>
                        </div>
                    @endif

                    {{-- Action buttons --}}
                    <div class="flex gap-2 p-3.5 sm:p-4 pt-3 ml-[52px]">
                        @if($s->isUsed())
                            <form method="POST" action="{{ route('admin.suggestions.unmark-used', $s) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full h-10 rounded-xl text-sm font-medium border border-border text-muted-text hover:bg-muted active:scale-[0.97] transition touch-manipulation">
                                    {{ __('app.suggest_unmark_used') }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.suggestions.mark-used', $s) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full h-10 rounded-xl text-sm font-bold bg-accent text-on-accent hover:bg-accent-hover active:scale-[0.97] transition touch-manipulation flex items-center justify-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ __('app.suggest_mark_used') }}
                                </button>
                            </form>
                            @if($s->status !== 'rejected')
                                <form method="POST" action="{{ route('admin.suggestions.reject', $s) }}">
                                    @csrf
                                    <button type="submit" class="h-10 px-4 rounded-xl text-sm font-medium border border-border text-error hover:bg-error-bg active:scale-[0.97] transition touch-manipulation">
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
@endif
@endsection
