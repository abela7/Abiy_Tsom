@extends('layouts.admin')
@section('title', __('app.themes'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.themes') }}</h1>
    @if($season)
        <a href="{{ route('admin.themes.create') }}" class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">+ {{ __('app.create') }}</a>
    @endif
</div>

@if(!$season)
    <p class="text-muted-text">{{ __('app.no_active_season') }} <a href="{{ route('admin.seasons.create') }}" class="text-accent hover:underline">{{ __('app.create_one_first') }}</a></p>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($themes as $theme)
            <div class="bg-card rounded-xl shadow-sm border border-border p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold bg-accent/10 text-accent px-2 py-0.5 rounded-full">{{ __('app.week_num', ['num' => $theme->week_number]) }}</span>
                    <a href="{{ route('admin.themes.edit', $theme) }}" class="text-sm text-accent hover:underline">{{ __('app.edit') }}</a>
                </div>
                <h3 class="font-bold text-primary">{{ $theme->name_en }}</h3>
                @if($theme->name_geez)<p class="text-sm text-muted-text">{{ $theme->name_geez }}</p>@endif
                <p class="text-sm text-secondary mt-1">{{ $theme->meaning }}</p>
                @if($theme->gospel_reference)
                    <p class="text-xs text-accent-secondary mt-2 font-medium">{{ $theme->gospel_reference }}</p>
                @endif
                @if($theme->epistles_reference)
                    <p class="text-xs text-muted-text mt-0.5">{{ $theme->epistles_reference }}</p>
                @endif
                @if($theme->liturgy)
                    <p class="text-xs text-muted-text italic mt-0.5">{{ $theme->liturgy }}</p>
                @endif
                <p class="text-xs text-muted-text mt-2">{{ $theme->week_start_date->format('M d') }} - {{ $theme->week_end_date->format('M d') }}</p>
            </div>
        @empty
            <p class="text-muted-text col-span-2">{{ __('app.no_weekly_themes_yet') }}</p>
        @endforelse
    </div>
@endif
@endsection
