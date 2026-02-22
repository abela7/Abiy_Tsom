@extends('layouts.admin')
@section('title', __('app.suggest_content_suggestions'))

@section('content')
<div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.suggest_content_suggestions') }}</h1>
    <a href="{{ route('suggest') }}" target="_blank" rel="noopener"
       class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-on-accent transition hover:bg-accent-hover">
        {{ __('app.suggest_page_title') }}
    </a>
</div>

{{-- Filter tabs --}}
<div class="flex flex-wrap gap-2 mb-5">
    @foreach(['all' => __('app.suggest_all'), 'pending' => __('app.suggest_status_pending'), 'used' => __('app.suggest_status_used'), 'rejected' => __('app.suggest_status_rejected')] as $key => $label)
        <a href="{{ route('admin.suggestions.index', ['status' => $key]) }}"
           class="px-3.5 py-1.5 rounded-full text-sm font-medium border transition {{ $filter === $key ? 'bg-accent text-on-accent border-accent' : 'bg-card text-secondary border-border hover:border-accent/40' }}">
            {{ $label }}
            <span class="ml-1 text-xs opacity-70">({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

{{-- ═══ Desktop table (hidden on mobile) ═══ --}}
<div class="hidden md:block bg-card rounded-xl shadow-sm border border-border overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-muted border-b border-border">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.suggest_type_label') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.suggest_title_label') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.suggest_language_label') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.suggest_submitter_label') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.status') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.date_label') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse($suggestions as $s)
                <tr class="hover:bg-muted/50 transition-colors" x-data="{ expanded: false }">
                    <td class="px-4 py-3 font-medium text-accent">{{ $s->typeLabel() }}</td>
                    <td class="px-4 py-3">
                        <button type="button" @click="expanded = !expanded" class="text-left hover:text-accent transition">
                            <span class="font-medium text-primary">{{ $s->title ?: '-' }}</span>
                            @if($s->content_detail)
                                <svg class="w-3.5 h-3.5 inline ml-1 text-muted-text transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            @endif
                        </button>
                        <div x-show="expanded" x-cloak x-transition class="mt-2 space-y-1 text-xs text-secondary">
                            @if($s->reference)<p><span class="font-semibold">Ref:</span> {{ $s->reference }}</p>@endif
                            @if($s->author)<p><span class="font-semibold">Author:</span> {{ $s->author }}</p>@endif
                            @if($s->content_detail)<p class="whitespace-pre-wrap">{{ $s->content_detail }}</p>@endif
                            @if($s->notes)<p class="text-muted-text italic">{{ $s->notes }}</p>@endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-secondary uppercase text-xs font-bold">{{ $s->language }}</td>
                    <td class="px-4 py-3 text-secondary">
                        {{ $s->displayName() }}
                        @if($s->user)
                            <span class="text-xs text-accent font-medium">({{ $s->user->role }})</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($s->isUsed())
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success">{{ __('app.suggest_status_used') }}</span>
                            <p class="text-[10px] text-muted-text mt-0.5">{{ __('app.suggest_used_by', ['name' => $s->usedBy?->name ?? '?', 'date' => $s->used_at->format('M d')]) }}</p>
                        @elseif($s->status === 'rejected')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-error-bg text-error">{{ __('app.suggest_status_rejected') }}</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-reflection-bg text-accent-secondary">{{ __('app.suggest_status_pending') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-muted-text">{{ $s->created_at->format('M d, H:i') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1.5">
                            @if($s->isUsed())
                                <form method="POST" action="{{ route('admin.suggestions.unmark-used', $s) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-muted-text hover:text-primary transition">{{ __('app.suggest_unmark_used') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.suggestions.mark-used', $s) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-accent hover:underline font-semibold">{{ __('app.suggest_mark_used') }}</button>
                                </form>
                                @if($s->status !== 'rejected')
                                    <form method="POST" action="{{ route('admin.suggestions.reject', $s) }}">
                                        @csrf
                                        <button type="submit" class="text-xs text-error hover:underline">{{ __('app.suggest_reject') }}</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-muted-text">{{ __('app.suggest_no_suggestions') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ═══ Mobile card list (hidden on desktop) ═══ --}}
<div class="md:hidden space-y-3">
    @forelse($suggestions as $s)
        <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden" x-data="{ expanded: false }">
            <div class="p-4 space-y-3">
                {{-- Top row: type badge + status --}}
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-accent/10 text-accent text-xs font-bold">
                            {{ $s->typeLabel() }}
                        </span>
                        <span class="text-xs font-bold text-muted-text uppercase">{{ $s->language }}</span>
                    </div>
                    @if($s->isUsed())
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-success-bg text-success shrink-0">{{ __('app.suggest_status_used') }}</span>
                    @elseif($s->status === 'rejected')
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-error-bg text-error shrink-0">{{ __('app.suggest_status_rejected') }}</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-reflection-bg text-accent-secondary shrink-0">{{ __('app.suggest_status_pending') }}</span>
                    @endif
                </div>

                {{-- Title + detail --}}
                <button type="button" @click="expanded = !expanded" class="text-left w-full">
                    <p class="text-sm font-semibold text-primary leading-tight">{{ $s->title ?: '-' }}</p>
                    @if($s->reference)
                        <p class="text-xs text-muted-text mt-0.5">{{ $s->reference }}</p>
                    @endif
                </button>

                <div x-show="expanded" x-cloak x-transition class="space-y-1.5 text-xs text-secondary border-t border-border pt-2">
                    @if($s->author)<p><span class="font-semibold">Author:</span> {{ $s->author }}</p>@endif
                    @if($s->content_detail)<p class="whitespace-pre-wrap">{{ $s->content_detail }}</p>@endif
                    @if($s->notes)<p class="text-muted-text italic">{{ $s->notes }}</p>@endif
                </div>

                {{-- Meta --}}
                <div class="flex items-center justify-between text-xs text-muted-text">
                    <span>
                        {{ __('app.suggest_submitted_by', ['name' => $s->displayName()]) }}
                        @if($s->user)
                            <span class="text-accent">({{ $s->user->role }})</span>
                        @endif
                    </span>
                    <span>{{ $s->created_at->format('M d') }}</span>
                </div>

                @if($s->isUsed())
                    <p class="text-[10px] text-muted-text">{{ __('app.suggest_used_by', ['name' => $s->usedBy?->name ?? '?', 'date' => $s->used_at->format('M d')]) }}</p>
                @endif
            </div>

            {{-- Action bar --}}
            <div class="flex border-t border-border divide-x divide-border">
                @if($s->isUsed())
                    <form method="POST" action="{{ route('admin.suggestions.unmark-used', $s) }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full py-2.5 text-center text-sm font-medium text-muted-text hover:bg-muted/50 transition">{{ __('app.suggest_unmark_used') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.suggestions.mark-used', $s) }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full py-2.5 text-center text-sm font-semibold text-accent hover:bg-muted/50 transition">{{ __('app.suggest_mark_used') }}</button>
                    </form>
                    @if($s->status !== 'rejected')
                        <form method="POST" action="{{ route('admin.suggestions.reject', $s) }}" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full py-2.5 text-center text-sm font-medium text-error hover:bg-muted/50 transition">{{ __('app.suggest_reject') }}</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    @empty
        <div class="bg-card rounded-xl border border-border p-8 text-center text-muted-text">
            {{ __('app.suggest_no_suggestions') }}
        </div>
    @endforelse
</div>
@endsection
