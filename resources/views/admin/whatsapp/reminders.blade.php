@extends('layouts.admin')
@section('title', __('app.whatsapp_reminders_tab'))

@section('content')
@include('admin.whatsapp._nav')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.whatsapp_reminders_tab') }}</h1>
    <p class="text-sm text-muted-text mt-1">{{ __('app.whatsapp_reminders_help') }}</p>
</div>

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.whatsapp_opted_in_total') }}</p>
        <p class="text-2xl font-black text-accent mt-1">{{ number_format($totalOptedIn) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.whatsapp_time_slots') }}</p>
        <p class="text-2xl font-black text-accent-secondary mt-1">{{ number_format($byTime->count()) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- By time --}}
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
        <div class="px-4 py-3 border-b border-border">
            <h2 class="text-sm font-bold text-primary">{{ __('app.whatsapp_members_by_time') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted">
                    <tr>
                        <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.whatsapp_reminder_time') }}</th>
                        <th class="text-right px-4 py-2 font-semibold text-secondary">{{ __('app.count') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($byTime as $row)
                        <tr class="hover:bg-muted/50">
                            <td class="px-4 py-2 font-medium">{{ \Carbon\Carbon::parse($row->time)->format('H:i') }} {{ __('app.london_time') }}</td>
                            <td class="px-4 py-2 text-right font-bold text-accent">{{ $row->count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-8 text-center text-muted-text">{{ __('app.whatsapp_no_opted_in') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Members list --}}
<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <div class="px-4 py-3 border-b border-border">
        <h2 class="text-sm font-bold text-primary">{{ __('app.whatsapp_members_list') }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted">
                <tr>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.baptism_name') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.whatsapp_phone') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.whatsapp_reminder_time') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.whatsapp_last_sent') }}</th>
                    <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.registered') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($members as $m)
                    <tr class="hover:bg-muted/50">
                        <td class="px-4 py-2 font-medium">{{ $m->baptism_name ?: '—' }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $m->whatsapp_phone ? maskPhone($m->whatsapp_phone) : '—' }}</td>
                        <td class="px-4 py-2">{{ $m->whatsapp_reminder_time ? \Carbon\Carbon::parse($m->whatsapp_reminder_time)->format('H:i') : '—' }} {{ __('app.london_time') }}</td>
                        <td class="px-4 py-2">{{ $m->whatsapp_last_sent_date ? $m->whatsapp_last_sent_date->format('Y-m-d') : __('app.never') }}</td>
                        <td class="px-4 py-2 text-muted-text">{{ $m->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-muted-text">{{ __('app.whatsapp_no_opted_in') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
        <div class="px-4 py-3 border-t border-border">
            {{ $members->links() }}
        </div>
    @endif
</div>
@endsection
