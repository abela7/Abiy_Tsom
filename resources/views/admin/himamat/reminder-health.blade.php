@extends('layouts.admin')

@section('title', __('app.himamat_reminder_health_title'))

@section('content')
@php
    $formatSeconds = function (?int $seconds): string {
        if ($seconds === null) {
            return '—';
        }

        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = intdiv($seconds, 60);
        $remainder = $seconds % 60;

        return $remainder > 0 ? "{$minutes}m {$remainder}s" : "{$minutes}m";
    };
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.himamat_reminder_health_title') }}</h1>
            <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_reminder_health_subtitle') }}</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <a href="{{ route('admin.himamat.index') }}"
               class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.back') }}
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.himamat.reminder-health') }}" class="grid gap-4 rounded-2xl border border-border bg-card p-5 shadow-sm lg:grid-cols-[minmax(0,1fr)_auto]">
        <div>
            <label for="date" class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.date') }}</label>
            <input id="date"
                   type="date"
                   name="date"
                   value="{{ $selectedDate }}"
                   class="mt-2 w-full rounded-xl border border-border bg-white px-4 py-3 text-sm text-primary">
            <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_timezone_label') }}: {{ $timezone }}</p>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-accent px-4 py-3 text-sm font-semibold text-on-accent transition hover:bg-accent-hover">
                {{ __('app.filter') }}
            </button>
            <a href="{{ route('admin.himamat.reminder-health') }}"
               class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-3 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.clear_filters') }}
            </a>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reminder_health_runs_due') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $summary['total_runs'] }}</p>
            <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_reminder_health_processing') }}: {{ $summary['processing_runs'] }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reminder_health_success_rate') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">
                {{ $summary['success_rate'] !== null ? $summary['success_rate'].'%' : '—' }}
            </p>
            <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_reminder_health_completed') }}: {{ $summary['completed_runs'] }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reminder_health_failures') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $summary['total_failed'] }}</p>
            <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_reminder_health_missed') }}: {{ $summary['missed_runs'] }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reminder_health_avg_latency') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $formatSeconds($summary['avg_finish_delay_seconds']) }}</p>
            <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_reminder_health_throughput') }}: {{ $summary['avg_throughput_per_minute'] !== null ? $summary['avg_throughput_per_minute'].'/min' : '—' }}</p>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reminder_health_recipients') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $summary['total_recipients'] }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reminder_health_sent') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $summary['total_sent'] }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reminder_health_skipped') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $summary['total_skipped'] }}</p>
            <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_reminder_health_partial') }}: {{ $summary['partial_runs'] }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reminder_health_dispatch_delay') }}</p>
            <p class="mt-3 text-3xl font-bold text-primary">{{ $formatSeconds($summary['avg_start_delay_seconds']) }}</p>
        </div>
    </div>

    <div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border text-sm">
                <thead class="bg-muted/60">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.day') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.time') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.status') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_tracking_selected_slots') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_reminder_health_recipients') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_reminder_health_sent') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_reminder_health_failures') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_reminder_health_skipped') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_reminder_health_dispatch_delay') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-primary">{{ __('app.himamat_reminder_health_avg_latency') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border bg-card">
                    @forelse($runs as $run)
                        @php
                            $slot = $run->himamatSlot;
                            $day = $slot?->himamatDay;
                            $dispatchDelaySeconds = $run->dispatch_started_at && $run->due_at_london
                                ? max(0, $run->due_at_london->diffInSeconds($run->dispatch_started_at))
                                : null;
                            $finishDelaySeconds = $run->dispatch_finished_at && $run->due_at_london
                                ? max(0, $run->due_at_london->diffInSeconds($run->dispatch_finished_at))
                                : null;
                            $statusClass = match($run->status) {
                                'completed' => 'bg-success-bg text-success',
                                'completed_with_failures' => 'bg-warning-bg text-warning',
                                'missed' => 'bg-danger-bg text-danger',
                                default => 'bg-muted text-muted-text',
                            };
                        @endphp
                        <tr>
                            <td class="px-4 py-4 align-top">
                                <p class="font-semibold text-primary">{{ localized($day, 'title') ?? $day?->title_en ?? '—' }}</p>
                                <p class="mt-1 text-xs text-secondary">{{ $day?->slug ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-4 align-top text-secondary">{{ optional($run->due_at_london)->format('H:i') ?? '—' }}</td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ __("app.himamat_dispatch_status_{$run->status}") }}
                                </span>
                                @if($run->last_error)
                                    <p class="mt-2 max-w-xs text-xs text-secondary">{{ $run->last_error }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top text-secondary">{{ localized($slot, 'slot_header') ?? $slot?->slot_header_en ?? '—' }}</td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $run->recipient_count }}</td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $run->sent_count }}</td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $run->failed_count }}</td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $run->skipped_count }}</td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $formatSeconds($dispatchDelaySeconds) }}</td>
                            <td class="px-4 py-4 align-top text-secondary">{{ $formatSeconds($finishDelaySeconds) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-muted-text">
                                {{ __('app.himamat_reminder_health_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
