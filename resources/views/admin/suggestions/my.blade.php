@extends('layouts.admin')
@section('title', __('app.suggest_my_suggestions'))

@section('content')
<div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.suggest_my_suggestions') }}</h1>
    <a href="{{ route('suggest') }}"
       class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-on-accent transition hover:bg-accent-hover">
        {{ __('app.suggest_page_title') }}
    </a>
</div>

@if($suggestions->isEmpty())
    <div class="bg-card rounded-xl border border-border p-8 text-center text-muted-text">
        {{ __('app.suggest_no_suggestions') }}
    </div>
@else
    <div class="space-y-3">
        @foreach($suggestions as $s)
            <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden" x-data="{ expanded: false }">
                <div class="p-4 space-y-2.5">
                    {{-- Top row --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2">
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

                    {{-- Title --}}
                    <button type="button" @click="expanded = !expanded" class="text-left w-full">
                        <p class="text-sm font-semibold text-primary leading-tight">{{ $s->title ?: '-' }}</p>
                        @if($s->reference)
                            <p class="text-xs text-muted-text mt-0.5">{{ $s->reference }}</p>
                        @endif
                    </button>

                    {{-- Expandable detail --}}
                    <div x-show="expanded" x-cloak x-transition class="space-y-1.5 text-xs text-secondary border-t border-border pt-2">
                        @if($s->author)<p><span class="font-semibold">Author:</span> {{ $s->author }}</p>@endif
                        @if($s->content_detail)<p class="whitespace-pre-wrap">{{ $s->content_detail }}</p>@endif
                        @if($s->notes)<p class="text-muted-text italic">{{ $s->notes }}</p>@endif
                    </div>

                    {{-- Footer --}}
                    <div class="text-xs text-muted-text pt-1">
                        {{ __('app.suggest_submitted_at', ['date' => $s->created_at->format('M d, Y')]) }}
                        @if($s->isUsed())
                            <span class="block mt-0.5 text-success font-medium">
                                {{ __('app.suggest_used_by', ['name' => $s->usedBy?->name ?? '?', 'date' => $s->used_at->format('M d, Y')]) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
