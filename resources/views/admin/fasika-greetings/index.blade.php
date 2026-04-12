@extends('layouts.admin')

@section('title', __('app.fasika_greeting_admin_title'))

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-primary">{{ __('app.fasika_greeting_admin_title') }}</h1>
            <p class="mt-1 text-sm text-muted-text">{{ __('app.fasika_greeting_admin_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('public.yefasika-beal') }}"
               target="_blank"
               class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2 text-sm font-semibold text-primary transition hover:bg-muted/50">
                {{ __('app.fasika_greeting_open_public_page') }}
            </a>
            @if($stats['created'] > 0)
                <form method="POST"
                      action="{{ route('admin.fasika-greetings.clear-all') }}"
                      onsubmit="return confirm('{{ __('app.fasika_greeting_clear_all_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl border border-error/30 bg-error/10 px-4 py-2 text-sm font-semibold text-error transition hover:bg-error/15">
                        {{ __('app.fasika_greeting_clear_all_button') }}
                    </button>
                </form>
            @endif
        </div>
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
                        <th class="px-4 py-3 text-left font-semibold text-secondary">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($shares as $share)
                        <tr class="align-top">
                            <td class="px-4 py-3 text-primary">
                                <form method="POST"
                                      action="{{ route('admin.fasika-greetings.update', $share) }}"
                                      class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                                    @csrf
                                    @method('PATCH')
                                    <label class="sr-only" for="sender-{{ $share->id }}">{{ __('app.fasika_greeting_sender_name') }}</label>
                                    <input id="sender-{{ $share->id }}"
                                           type="text"
                                           name="sender_name"
                                           value="{{ session('fasika_greeting_failed_token') === $share->share_token ? old('sender_name', $share->sender_name) : $share->sender_name }}"
                                           maxlength="120"
                                           required
                                           class="min-w-[10rem] max-w-[18rem] flex-1 rounded-lg border border-border bg-surface px-3 py-2 text-sm font-semibold text-primary shadow-inner outline-none transition placeholder:text-muted-text focus:border-accent/50 focus:ring-2 focus:ring-accent/20">
                                    <button type="submit"
                                            class="inline-flex shrink-0 items-center justify-center rounded-lg border border-accent/40 bg-accent/10 px-3 py-2 text-xs font-semibold text-accent transition hover:bg-accent/15">
                                        {{ __('app.fasika_greeting_update_button') }}
                                    </button>
                                </form>
                            </td>
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
                            <td class="px-4 py-3">
                                <form method="POST"
                                      action="{{ route('admin.fasika-greetings.destroy', $share) }}"
                                      onsubmit="return confirm('{{ __('app.fasika_greeting_delete_confirm', ['name' => $share->sender_name]) }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-lg border border-error/30 bg-error/10 px-3 py-2 text-xs font-semibold text-error transition hover:bg-error/15">
                                        {{ __('app.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-muted-text">{{ __('app.fasika_greeting_empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $shares->links() }}
</div>
@endsection
