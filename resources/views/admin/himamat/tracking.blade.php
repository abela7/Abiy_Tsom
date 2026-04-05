@extends('layouts.admin')

@section('title', __('app.himamat_tracking_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.himamat_tracking_title') }}</h1>
            <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_tracking_subtitle') }}</p>
        </div>
        <a href="{{ route('admin.himamat.index') }}"
           class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
            {{ __('app.back') }}
        </a>
    </div>

    <form method="GET" action="{{ route('admin.himamat.tracking') }}" class="grid gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
        <div>
            <label for="campaign" class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_tracking_campaign') }}</label>
            <select id="campaign" name="campaign" class="mt-2 w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-primary">
                @if($campaigns->isEmpty())
                    <option value="">{{ __('app.himamat_tracking_no_campaigns') }}</option>
                @else
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign }}" @selected($selectedCampaign === $campaign)>{{ $campaign }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div>
            <label for="search" class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.search') }}</label>
            <input id="search"
                   type="text"
                   name="search"
                   value="{{ $search }}"
                   placeholder="{{ __('app.himamat_tracking_search_placeholder') }}"
                   class="mt-2 w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-primary placeholder:text-muted-text">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-on-accent transition hover:bg-accent-hover">
                {{ __('app.filter') }}
            </button>
            <a href="{{ route('admin.himamat.tracking') }}"
               class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-3 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.clear_filters') }}
            </a>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_tracking_total_sent') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $totalSent }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_tracking_total_clicked') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $totalClicked }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_tracking_total_not_clicked') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $totalNotClicked }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_tracking_total_preferences') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $totalPreferencesRecorded }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-muted/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.name') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.whatsapp') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.language') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_tracking_sent_at') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_tracking_clicked_at') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_tracking_open_count') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_tracking_selected_slots') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_tracking_unselected_slots') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border bg-card">
                    @forelse($deliveries as $delivery)
                        @php
                            $member = $delivery->member;
                            $preference = $member?->himamatPreferences?->first();
                            $selectedSlots = collect($slotDefinitions)->filter(fn ($slot) => $preference && $preference->slotEnabled($slot['key']));
                            $unselectedSlots = collect($slotDefinitions)->filter(fn ($slot) => ! $preference || ! $preference->slotEnabled($slot['key']));
                        @endphp
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <p class="font-semibold text-primary">{{ $member?->baptism_name ?? __('app.not_set') }}</p>
                                <p class="mt-1 text-xs text-secondary">{{ $delivery->campaign_key }}</p>
                            </td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $delivery->destination_phone ?: ($member?->whatsapp_phone ?? '—') }}</td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $member?->locale ?: 'am' }}</td>
                            <td class="px-4 py-4 align-top text-secondary">{{ optional($delivery->delivered_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-4 align-top">
                                @if($delivery->first_opened_at)
                                    <p class="font-semibold text-success">{{ optional($delivery->last_opened_at)->format('Y-m-d H:i') }}</p>
                                @else
                                    <span class="inline-flex rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-text">{{ __('app.himamat_tracking_not_clicked') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $delivery->open_count }}</td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex flex-wrap gap-2">
                                    @forelse($selectedSlots as $slot)
                                        <span class="rounded-full bg-success-bg px-2.5 py-1 text-xs font-semibold text-success">{{ $slot['label'] }}</span>
                                    @empty
                                        <span class="text-xs text-muted-text">{{ __('app.himamat_tracking_no_preferences') }}</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex flex-wrap gap-2">
                                    @forelse($unselectedSlots as $slot)
                                        <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-semibold text-muted-text">{{ $slot['label'] }}</span>
                                    @empty
                                        <span class="text-xs text-muted-text">—</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-muted-text">
                                {{ __('app.himamat_tracking_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deliveries->hasPages())
            <div class="border-t border-border px-4 py-4">
                {{ $deliveries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
