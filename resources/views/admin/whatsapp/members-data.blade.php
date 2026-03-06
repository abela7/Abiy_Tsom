@extends('layouts.admin')
@section('title', __('app.whatsapp_members_data_tab'))

@section('content')
@include('admin.whatsapp._nav')

<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-primary">{{ __('app.whatsapp_members_data_tab') }}</h1>
    <p class="text-sm text-muted-text mt-1.5">{{ __('app.whatsapp_members_data_help') }}</p>
</div>

@if(count($duplicatePhones))
    <div class="mb-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700">
        <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">
            {{ __('app.whatsapp_duplicate_phones_warning', ['count' => count($duplicatePhones)]) }}
        </p>
        <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-mono">
            {{ implode(', ', $duplicatePhones) }}
        </p>
    </div>
@endif

<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <div class="px-5 py-4 border-b border-border flex items-center justify-between">
        <h2 class="text-sm font-semibold text-primary">{{ __('app.whatsapp_all_members_with_phone') }}</h2>
        <span class="text-xs text-muted-text tabular-nums">{{ $members->total() }} {{ __('app.members') }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="border-b border-border">
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">ID</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.baptism_name') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.whatsapp_phone') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.whatsapp_reminder_time') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.whatsapp_last_sent') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.whatsapp_activity') }}</th>
                    <th class="text-left px-5 py-3.5 font-medium text-muted-text text-xs uppercase tracking-wider">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($members as $m)
                    <tr class="hover:bg-muted/40 transition-colors {{ in_array($m->whatsapp_phone, $duplicatePhones) ? 'bg-amber-50/50 dark:bg-amber-900/20' : '' }}">
                        <td class="px-5 py-3.5 text-muted-text tabular-nums">{{ $m->id }}</td>
                        <td class="px-5 py-3.5 font-medium text-primary">
                            {{ $m->baptism_name ?: '—' }}
                            @if(in_array($m->whatsapp_phone, $duplicatePhones))
                                <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">{{ __('app.duplicate') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 font-mono text-xs text-secondary">{{ $m->whatsapp_phone }}</td>
                        <td class="px-5 py-3.5 text-secondary">
                            {{ $m->whatsapp_reminder_time ? \Carbon\Carbon::parse($m->whatsapp_reminder_time)->format('H:i') : '—' }}
                        </td>
                        <td class="px-5 py-3.5 text-secondary">{{ $m->whatsapp_language ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-secondary">{{ $m->whatsapp_last_sent_date ? $m->whatsapp_last_sent_date->format('Y-m-d') : '—' }}</td>
                        <td class="px-5 py-3.5 text-secondary tabular-nums text-xs">
                            {{ $m->sessions_count }} {{ __('app.whatsapp_sessions') }}
                        </td>
                        <td class="px-5 py-3.5">
                            <form method="POST" action="{{ route('admin.whatsapp.members-data.destroy', $m) }}"
                                  x-data @submit.prevent="if (confirm('{{ __('app.member_delete_confirm') }}')) $el.submit()">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-medium bg-error-bg text-error hover:opacity-90 transition">
                                    {{ __('app.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-muted-text">{{ __('app.whatsapp_no_members_data') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($members->hasPages())
        <div class="px-5 py-4 border-t border-border">
            {{ $members->links() }}
        </div>
    @endif
</div>
@endsection
