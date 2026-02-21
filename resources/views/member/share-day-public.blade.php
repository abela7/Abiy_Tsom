@extends('layouts.member-guest')

@section('title', $dayTitle . ' - ' . __('app.app_name'))

@section('content')
<div class="space-y-4">
    <div class="bg-card rounded-2xl sm:rounded-3xl shadow-2xl shadow-black/10 dark:shadow-black/30 border border-border overflow-hidden">
        <div class="px-5 py-5 sm:px-7 sm:py-7">
            <p class="text-[11px] font-bold text-muted-text uppercase tracking-widest">
                {{ __('app.day_x', ['day' => $daily->day_number]) }}
            </p>
            <h1 class="text-xl sm:text-2xl font-black text-primary mt-1 leading-tight">
                {{ $dayTitle }}
            </h1>

            @if($weekName)
                <p class="text-sm text-muted-text mt-2">{{ $weekName }}</p>
            @endif

            @if($bibleReference)
                <div class="mt-4 p-3 rounded-xl bg-muted/40 border border-border">
                    <p class="text-xs font-bold text-muted-text uppercase tracking-widest mb-1">{{ __('app.bible') }}</p>
                    <p class="text-sm font-semibold text-primary">{{ $bibleReference }}</p>
                </div>
            @endif

            @if($reflection)
                <div class="mt-4">
                    <p class="text-xs font-bold text-muted-text uppercase tracking-widest mb-2">{{ __('app.daily_reflection') }}</p>
                    <div class="text-sm text-secondary leading-relaxed whitespace-pre-wrap">{{ $reflection }}</div>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-accent text-on-accent rounded-xl font-semibold text-sm hover:bg-accent-hover transition">
                    {{ __('app.nav_home') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
