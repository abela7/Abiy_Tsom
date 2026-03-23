@extends('layouts.member')

@section('title', __('app.today_content_not_ready_title') . ' - ' . __('app.app_name'))

@section('content')
<div class="px-4 pt-6">
    <div class="mx-auto max-w-md">
        <div class="overflow-hidden rounded-3xl border border-border bg-card shadow-lg">
            <div class="bg-gradient-to-br from-accent via-accent to-accent-hover px-6 py-8 text-white">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-sm">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-black leading-tight">{{ __('app.today_content_not_ready_title') }}</h1>
                <p class="mt-2 text-sm leading-relaxed text-white/85">{{ __('app.today_content_not_ready_body') }}</p>
            </div>

            <div class="space-y-3 px-6 py-6">
                <a href="{{ memberUrl('/calendar') }}"
                   class="flex w-full items-center justify-center rounded-2xl bg-accent px-5 py-4 text-base font-bold text-on-accent shadow-lg shadow-accent/20 transition hover:opacity-90 active:scale-[0.98]">
                    {{ __('app.check_other_days') }}
                </a>

                <a href="{{ memberUrl('/home') }}"
                   class="flex w-full items-center justify-center rounded-2xl border border-border px-5 py-4 text-base font-semibold text-primary transition hover:bg-muted/50 active:scale-[0.98]">
                    {{ __('app.back') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
