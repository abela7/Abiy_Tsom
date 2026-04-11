@extends('layouts.admin')

@section('title', __('app.fasika_greeting_admin_title'))

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-primary">{{ __('app.fasika_greeting_admin_title') }}</h1>
            <p class="mt-1 text-sm text-muted-text">{{ __('app.fasika_greeting_admin_subtitle') }}</p>
        </div>
        <a href="{{ route('public.yefasika-beal') }}"
           target="_blank"
           class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2 text-sm font-semibold text-primary transition hover:bg-muted/50">
            {{ __('app.fasika_greeting_open_public_page') }}
        </a>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">{{ __('app.fasika_greeting_stat_created') }}</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['created']) }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">{{ __('app.fasika_greeting_stat_active') }}</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">{{ __('app.fasika_greeting_stat_opens') }}</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['opens']) }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted-text">{{ __('app.fasika_greeting_stat_unique_senders') }}</p>
            <p class="mt-3 text-3xl font-black text-primary">{{ number_format($stats['unique_senders']) }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-border bg-card">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-surface/70">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_greeting_sender_name') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_greeting_opens') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_greeting_created_at') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_greeting_last_opened_at') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.fasika_greeting_link') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($shares as $share)
                        <tr class="align-top">
                            <td class="px-4 py-3 text-primary font-semibold">{{ $share->sender_name }}</td>
                            <td class="px-4 py-3 text-primary">{{ number_format($share->open_count) }}</td>
                            <td class="px-4 py-3 text-primary">{{ $share->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-primary">{{ $share->last_opened_at?->format('Y-m-d H:i') ?? __('app.never') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('public.yefasika-beal.share', $share) }}"
                                   target="_blank"
                                   class="break-all text-accent hover:underline">
                                    {{ route('public.yefasika-beal.share', $share) }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-muted-text">{{ __('app.fasika_greeting_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $shares->links() }}
</div>
@endsection
