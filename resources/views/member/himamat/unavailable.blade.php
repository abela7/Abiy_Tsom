@extends('layouts.member')

@section('title', __('app.himamat_title').' - '.__('app.app_name'))

@section('content')
<div class="px-4 pt-6 pb-12">
    <div class="rounded-[2rem] border border-border bg-card p-6 shadow-sm">
        <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-accent/10 text-accent">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="mt-4 text-2xl font-bold text-primary">{{ __('app.himamat_unavailable_title') }}</h1>
        <p class="mt-2 text-sm leading-relaxed text-secondary">{{ __('app.himamat_unavailable_body') }}</p>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
            <a href="{{ memberUrl('/home') }}"
               class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-on-accent transition hover:bg-accent-hover">
                {{ __('app.nav_home') }}
            </a>
            <a href="{{ memberUrl('/calendar') }}"
               class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-3 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.nav_calendar') }}
            </a>
        </div>
    </div>
</div>
@endsection
