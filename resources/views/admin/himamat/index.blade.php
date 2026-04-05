@extends('layouts.admin')

@section('title', __('app.himamat_title'))

@section('content')
<div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.himamat_title') }}</h1>
        <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_admin_subtitle') }}</p>
    </div>
    <div class="flex flex-col gap-2 sm:flex-row">
        <a href="{{ route('admin.himamat.reminder-health') }}"
           class="inline-flex w-full items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border sm:w-auto">
            {{ __('app.himamat_reminder_health_title') }}
        </a>
        <a href="{{ route('admin.himamat.tracking') }}"
           class="inline-flex w-full items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border sm:w-auto">
            {{ __('app.himamat_tracking_title') }}
        </a>
        <form action="{{ route('admin.himamat.scaffold') }}" method="POST">
            @csrf
            <button type="submit"
                    class="inline-flex w-full items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border sm:w-auto">
                {{ __('app.himamat_scaffold_action') }}
            </button>
        </form>
    </div>
</div>

@if(!$season)
    <p class="text-muted-text">
        {{ __('app.no_active_season') }}
        <a href="{{ route('admin.seasons.create') }}" class="text-accent hover:underline">{{ __('app.create_one_first') }}</a>
    </p>
@else
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($days as $day)
            <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ $day->date?->format('D, d M Y') }}</p>
                        <h2 class="mt-2 text-lg font-bold text-primary">{{ localized($day, 'title') ?? $day->title_en }}</h2>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $day->is_published ? 'bg-success-bg text-success' : 'bg-muted text-muted-text' }}">
                        {{ $day->is_published ? __('app.published') : __('app.draft') }}
                    </span>
                </div>

                <div class="mt-4 grid gap-2">
                    @foreach($day->slots->sortBy('slot_order') as $slot)
                        <div class="flex items-center justify-between rounded-xl bg-muted/50 px-3 py-2.5 text-sm">
                            <div>
                                <p class="font-semibold text-primary">{{ substr((string) $slot->scheduled_time_london, 0, 5) }}</p>
                                <p class="text-xs text-secondary">{{ localized($slot, 'slot_header') ?? $slot->slot_header_en }}</p>
                            </div>
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $slot->is_published ? 'bg-success-bg text-success' : 'bg-border text-muted-text' }}">
                                {{ $slot->is_published ? __('app.published') : __('app.draft') }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 flex gap-2">
                    <a href="{{ route('admin.himamat.preview', ['day' => $day->getKey()]) }}"
                       target="_blank" rel="noopener"
                       class="inline-flex flex-1 items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
                        {{ __('app.view') }}
                    </a>
                    <a href="{{ route('admin.himamat.edit', ['day' => $day->getKey()]) }}"
                       class="inline-flex flex-1 items-center justify-center rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-on-accent transition hover:bg-accent-hover">
                        {{ __('app.edit') }}
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-border bg-card p-8 text-center text-muted-text md:col-span-2 xl:col-span-3">
                {{ __('app.himamat_admin_empty') }}
            </div>
        @endforelse
    </div>
@endif
@endsection
